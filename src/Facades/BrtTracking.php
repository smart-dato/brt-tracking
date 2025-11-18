<?php

namespace SmartDato\BrtTracking\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SmartDato\BrtTracking\DTO\ShipmentData trackingByShipmentId(string $shipmentId, ?int $year = null, ?string $lang = null)
 * @method static string getShipmentIdByRMN(int|string $reference)
 * @method static string getShipmentIdByRMA(string $reference)
 * @method static array getShipmentIdByParcel(string $parcelId)
 * @method static array getLegendaEsiti(?string $lang = null)
 * @method static array getLegendaEventi(?string $lang = null)
 * @method static \SmartDato\BrtTracking\BrtTrackingClient setConfig(array $config)
 * @method static array getConfig()
 *
 * @see \SmartDato\BrtTracking\BrtTrackingClient
 */
class BrtTracking extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\BrtTracking\BrtTrackingClient::class;
    }
}
