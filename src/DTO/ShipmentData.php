<?php

namespace SmartDato\BrtTracking\DTO;

use DateTime;
use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * Data object representing a BRT shipment.
 *
 * @property-read string $id
 * @property-read DateTimeInterface $date
 * @property-read string $status1
 * @property-read ?string $status2
 * @property-read array $delivery
 * @property-read array $sender
 * @property-read array $recipient
 * @property-read array $goods
 * @property-read array $cod
 * @property-read array $insurance
 * @property-read EventData[] $events
 * @property-read NoteData[] $notes
 */
class ShipmentData extends Data
{
    public function __construct(
        public string $id,
        public DateTimeInterface $date,
        public string $status1,
        public ?string $status2,
        public array $delivery,
        public array $sender,
        public array $recipient,
        public array $goods,
        public array $cod,
        public array $insurance,
        /** @var EventData[] */
        public array $events,
        /** @var NoteData[] */
        public array $notes,
    ) {}

    /**
     * Create a ShipmentData instance from the SOAP response.
     */
    public static function fromSoap(object $r): self
    {
        $bolla = $r->BOLLA ?? new \stdClass;
        $ds = $bolla->DATI_SPEDIZIONE ?? new \stdClass;
        $dc = $bolla->DATI_CONSEGNA ?? new \stdClass;
        $mitt = $bolla->MITTENTE ?? new \stdClass;
        $dest = $bolla->DESTINATARIO ?? new \stdClass;
        $merce = $bolla->MERCE ?? new \stdClass;
        $cod = $bolla->CONTRASSEGNO ?? new \stdClass;
        $ass = $bolla->ASSICURAZIONE ?? new \stdClass;

        $events = [];
        foreach ((array) ($bolla->LISTA_EVENTI ?? []) as $e) {
            $events[] = new EventData(
                $e->ID ?? null,
                $e->FILIALE ?? null,
                $e->DATA ?? null,
                $e->ORA ?? null,
                $e->DESCRIZIONE ?? null,
            );
        }

        $notes = [];
        foreach ((array) ($bolla->LISTA_NOTE ?? []) as $n) {
            $notes[] = new NoteData($n->DESCRIZIONE ?? null);
        }

        return new self(
            id: (string) ($ds->SPEDIZIONE_ID ?? ''),
            date: new DateTime($ds->SPEDIZIONE_DATA ?? 'now'),
            status1: (string) ($ds->STATO_SPED_PARTE1 ?? ''),
            status2: $ds->STATO_SPED_PARTE2 ?? null,
            delivery: [
                'requested_date' => $dc->DATA_CONS_RICHIESTA ?? null,
                'theoretical_date' => $dc->DATA_TEORICA_CONSEGNA ?? null,
                'delivered_date' => $dc->DATA_CONSEGNA_MERCE ?? null,
                'requested_time_from' => $dc->ORA_TEORICA_CONSEGNA_DA ?? null,
                'requested_time_to' => $dc->ORA_TEORICA_CONSEGNA_A ?? null,
                'requested_type' => $dc->TIPO_CONS_RICHIESTA ?? null,
                'signer' => $dc->FIRMATARIO_CONSEGNA ?? null,
            ],
            sender: [
                'code' => $mitt->CODICE ?? null,
                'name' => $mitt->RAGIONE_SOCIALE ?? null,
                'zip' => $mitt->CAP ?? null,
                'city' => $mitt->LOCALITA ?? null,
                'address' => $mitt->INDIRIZZO ?? null,
                'province' => $mitt->SIGLA_AREA ?? null,
            ],
            recipient: [
                'name' => $dest->RAGIONE_SOCIALE ?? null,
                'zip' => $dest->CAP ?? null,
                'city' => $dest->LOCALITA ?? null,
                'address' => $dest->INDIRIZZO ?? null,
                'province' => $dest->SIGLA_PROVINCIA ?? null,
                'country' => $dest->SIGLA_NAZIONE ?? null,
                'contact' => $dest->REFERENTE_CONSEGNA ?? null,
                'phone' => $dest->TELEFONO_REFERENTE ?? null,
            ],
            goods: [
                'colli' => (int) ($merce->COLLI ?? 0),
                'natura' => $merce->NATURA_MERCE ?? null,
                'pesoKg' => isset($merce->PESO_KG) ? (float) $merce->PESO_KG : 0.0,
                'volumeM3' => isset($merce->VOLUME_M3) ? (float) $merce->VOLUME_M3 : 0.0,
            ],
            cod: [
                'currency' => $cod->CONTRASSEGNO_DIVISA ?? null,
                'amount' => isset($cod->CONTRASSEGNO_IMPORTO) ? (float) $cod->CONTRASSEGNO_IMPORTO : null,
                'incasso' => $cod->CONTRASSEGNO_INCASSO ?? null,
                'particolarita' => $cod->CONTRASSEGNO_PARTICOLARITA ?? null,
            ],
            insurance: [
                'currency' => $ass->ASSICURAZIONE_DIVISA ?? null,
                'amount' => isset($ass->ASSICURAZIONE_IMPORTO) ? (float) $ass->ASSICURAZIONE_IMPORTO : null,
            ],
            events: $events,
            notes: $notes
        );
    }
}
