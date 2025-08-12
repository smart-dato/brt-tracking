<?php

namespace SmartDato\BrtTracking;

use Illuminate\Support\Facades\RateLimiter;
use Psr\Log\LoggerInterface;
use SmartDato\BrtTracking\DTO\ShipmentData;
use SmartDato\BrtTracking\Exceptions\BrtException;
use SmartDato\BrtTracking\Support\WsdlCache;
use SoapClient;

class BrtTrackingClient
{
    public function __construct(
        protected array $config,
        protected LoggerInterface $logger,
        protected ?WsdlCache $wsdlCache = null
    ) {}

    /**
     * Create a SoapClient for the given WSDL key.
     *
     * If WSDL caching is enabled, the WSDL will be downloaded and patched
     * locally to replace any http:// addresses with https:// equivalents.
     *
     * @throws \SoapFault
     */
    protected function soap(string $wsdlKey): SoapClient
    {
        $wsdlUrl = $this->config['wsdl'][$wsdlKey] ?? throw new \RuntimeException("Unknown WSDL key {$wsdlKey}");
        if (($this->config['cache_wsdl_locally'] ?? false) && $this->wsdlCache) {
            $wsdlUrl = $this->wsdlCache->getPatched($wsdlUrl);
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                // TLS 1.2/1.3
                'crypto_method' => (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0) | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            ],
        ]);

        return new SoapClient($wsdlUrl, [
            'soap_version' => SOAP_1_1,
            'style' => SOAP_DOCUMENT,
            'use' => SOAP_LITERAL,
            'keep_alive' => false,
            'trace' => false,
            'exceptions' => true,
            'connection_timeout' => $this->config['timeout'] ?? 10,
            'stream_context' => $context,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            // set location when we know it (prevents http/https mismatches)
            'location' => $this->config['locations'][$wsdlKey] ?? null,
        ]);
    }

    /**
     * Apply a simple rate limiter to ensure we do not exceed the allowed
     * number of requests per minute.
     *
     * The limit can be configured via the throttle_per_minute setting.  If the
     * limit is reached, a BrtException is thrown.
     */
    protected function throttle(): void
    {
        $max = (int) ($this->config['throttle_per_minute'] ?? 250);
        $key = 'brt:soap:global';
        RateLimiter::attempt($key, $max, fn () => true, 60);
        if (RateLimiter::remaining($key, 60) <= 0) {
            throw BrtException::throttled('BRT minute quota exceeded');
        }
    }

    /**
     * Fetch a shipment by its BRT shipment id.
     *
     * @param  string  $shipmentId  The BRT shipment identifier (format FFFSSNNNNNNN).
     * @param  int|null  $year  Optional shipment year (0 for current).
     * @param  string|null  $lang  Two letter ISO 639-1 language code.
     *
     * @throws \SoapFault
     */
    public function trackingByShipmentId(string $shipmentId, ?int $year = null, ?string $lang = null): ShipmentData
    {
        $this->throttle();
        $client = $this->soap('tracking_by_id');
        $payload = [
            'arg0' => [
                'LINGUA_ISO639_ALPHA2' => $lang ?? ($this->config['language'] ?? ''),
                'SPEDIZIONE_ANNO' => $year ?? 0,
                'SPEDIZIONE_BRT_ID' => $shipmentId,
            ],
        ];
        $res = $client->__soapCall('BRT_TrackingByBRTshipmentID', [$payload]);
        $this->assertEsito($res->return->ESITO ?? null);

        return ShipmentData::fromSoap($res->return);
    }

    /**
     * Find a BRT shipment id by the sender's numeric reference (RMN).
     *
     * @param  int|string  $reference  The sender reference.
     * @return string The BRT shipment id.
     *
     * @throws \SoapFault
     */
    public function getShipmentIdByRMN(int|string $reference): string
    {
        $this->throttle();
        $client = $this->soap('id_by_rmn');
        $payload = [
            'arg0' => [
                'CLIENTE_ID' => $this->config['client_id'],
                'RIFERIMENTO_MITTENTE_NUMERICO' => (string) $reference,
            ],
        ];
        $res = $client->__soapCall('GetIdSpedizioneByRMN', [$payload]);
        $this->assertEsito($res->return->ESITO ?? null);

        return (string) $res->return->SPEDIZIONE_ID;
    }

    /**
     * Find a BRT shipment id by the sender's alphanumeric reference (RMA).
     *
     * @param  string  $reference  The sender reference.
     * @return string The BRT shipment id.
     *
     * @throws \SoapFault
     */
    public function getShipmentIdByRMA(string $reference): string
    {
        $this->throttle();
        $client = $this->soap('id_by_rma');
        $payload = [
            'arg0' => [
                'CLIENTE_ID' => $this->config['client_id'],
                'RIFERIMENTO_MITTENTE_ALFABETICO' => $reference,
            ],
        ];
        $res = $client->__soapCall('GetIdSpedizioneByRMA', [$payload]);
        $this->assertEsito($res->return->ESITO ?? null);

        return (string) $res->return->SPEDIZIONE_ID;
    }

    /**
     * Find a BRT shipment id and year by parcel identifier.
     *
     * @param  string  $parcelId  The parcel identifier.
     * @return array{year:int,id:string}
     *
     * @throws \SoapFault
     */
    public function getShipmentIdByParcel(string $parcelId): array
    {
        $this->throttle();
        $client = $this->soap('id_by_collo');
        $payload = [
            'arg0' => [
                'CLIENTE_ID' => $this->config['client_id'],
                'COLLO_ID' => $parcelId,
            ],
        ];
        $res = $client->__soapCall('GetIdSpedizioneByIdCollo', [$payload]);
        $this->assertEsito($res->return->ESITO ?? null);

        return [
            'year' => (int) ($res->SPEDIZIONE_ANNO ?? 0),
            'id' => (string) $res->SPEDIZIONE_ID,
        ];
    }

    /**
     * Retrieve a complete legend of status codes (esiti).
     *
     * The service returns data in batches of 20 records.  We continue to call
     * the service until the ESITO value is 100, indicating completion.
     *
     * @param  string|null  $lang  Two letter ISO 639-1 language code.
     * @return array<int,array{id:int,text1:string,text2:?string}>
     *
     * @throws \SoapFault
     */
    public function getLegendaEsiti(?string $lang = null): array
    {
        $client = $this->soap('legenda_esiti');
        $last = 0;
        $all = [];
        do {
            $this->throttle();
            $payload = [
                'arg0' => [
                    'LINGUA_ISO639_ALPHA2' => $lang ?? ($this->config['language'] ?? ''),
                    'ULTIMO_ID_RICEVUTO' => new \SoapVar((int) $last, XSD_INT),
                    'CLIENTE_ID' => (int) ($this->config['client_id'] ?? 0),
                ],
            ];

            $res = $client->__soapCall('GetLegendaEsiti', [$payload]);
            $this->assertEsito($res->return->ESITO ?? null, allow100: true);
            foreach ((array) ($res->return->LEGENDA ?? []) as $row) {
                $all[] = [
                    'id' => $row->ID,
                    'text1' => $row->TESTO1,
                    'text2' => $row->TESTO2 ?? null,
                ];
            }
            $last = $all ? (int) end($all)['id'] : $last;
        } while (($res->return->ESITO ?? 0) !== 100);

        return $all;
    }

    /**
     * Retrieve a complete legend of event codes.
     *
     * The service returns data in batches of 200 records.  We continue to call
     * the service until the ESITO value is 100, indicating completion.
     *
     * @param  string|null  $lang  Two letter ISO 639-1 language code.
     * @return array<int,array{id:string,description:string}>
     *
     * @throws \SoapFault
     */
    public function getLegendaEventi(?string $lang = null): array
    {
        $client = $this->soap('legenda_eventi');
        $last = '   ';
        $all = [];
        do {
            $this->throttle();
            $payload = [
                'arg0' => [
                    'LINGUA_ISO639_ALPHA2' => $lang ?? ($this->config['language'] ?? ''),
                    'ULTIMO_ID_RICEVUTO' => new \SoapVar((int) $last, XSD_INT),
                    'CLIENTE_ID' => (int) ($this->config['client_id'] ?? 0),
                ],
            ];

            $res = $client->__soapCall('GetLegendaEventi', [$payload]);
            $this->assertEsito($res->return->ESITO ?? null, allow100: true);
            foreach ((array) ($res->return->LEGENDA ?? []) as $row) {
                $all[] = [
                    'id' => $row->ID,
                    'description' => $row->DESCRIZIONE,
                ];
            }

            // For events the last received id is not numeric; pick the last one in the current batch.
            $last = $all ? $all[min(count($all), 200) - 1]['id'] : $last;
        } while (($res->return->ESITO ?? 0) !== 100);

        return $all;
    }

    /**
     * Interpret the ESITO code returned by the webservice.
     *
     * @param  mixed  $esito  The ESITO value from the service.
     * @param  bool  $allow100  Whether to treat 100 as a success.
     */
    protected function assertEsito($esito, bool $allow100 = false): void
    {
        if ($esito === 0 || ($allow100 && $esito === 100) || $esito === 2) {
            return;
        }
        throw BrtException::fromEsito((int) $esito);
    }
}
