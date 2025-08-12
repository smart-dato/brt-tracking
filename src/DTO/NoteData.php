<?php

namespace SmartDato\BrtTracking\DTO;

use Spatie\LaravelData\Data;

/**
 * Simple wrapper for notes returned by the BRT service.
 *
 * @property-read ?string $description
 */
class NoteData extends Data
{
    public function __construct(
        public ?string $description,
    ) {}
}
