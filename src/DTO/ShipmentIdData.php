<?php

namespace SmartDato\BrtTracking\DTO;

use Spatie\LaravelData\Data;

/**
 * Data object representing a BRT shipment ID lookup result.
 */
class ShipmentIdData extends Data
{
    public function __construct(
        public int $year,
        public string $id,
    ) {}

    /**
     * Create a ShipmentIdData instance from the SOAP response.
     */
    public static function fromSoap(object $return): self
    {
        return new self(
            year: (int) ($return->SPEDIZIONE_ANNO ?? 0),
            id: (string) ($return->SPEDIZIONE_ID ?? ''),
        );
    }
}
