<?php

namespace SmartDato\BrtTracking\DTO;

use Spatie\LaravelData\Data;

/**
 * Representation of a single tracking event returned by the BRT service.
 *
 * @property-read ?string $id
 * @property-read ?string $branch
 * @property-read ?string $date
 * @property-read ?string $time
 * @property-read ?string $description
 */
class EventData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $branch,
        public ?string $date,
        public ?string $time,
        public ?string $description,
    ) {}
}
