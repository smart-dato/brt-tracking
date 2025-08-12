<?php

namespace SmartDato\BrtTracking\Exceptions;

use RuntimeException;

/**
 * Exception thrown when the BRT service returns a non-zero ESITO code or when
 * the package detects a rate limit violation.
 */
class BrtException extends RuntimeException
{
    /**
     * Create an exception instance based on the ESITO code returned by the
     * webservice.  Unknown codes will produce a generic message.
     */
    public static function fromEsito(int $code): self
    {
        $map = [
            -1 => 'Generic/unknown error',
            -3 => 'DB connection error',
            -10 => 'Missing BRT shipment id',
            -11 => 'Shipment not found',
            -20 => 'Missing sender numeric reference',
            -21 => 'Missing client id',
            -22 => 'Multiple shipments found',
            -30 => 'Missing parcel id',
            100 => 'Data finished',
            2 => 'Unknown language; IT used',
        ];
        $message = $map[$code] ?? "BRT error ESITO={$code}";

        return new self($message, $code);
    }

    /**
     * Construct a throttling exception with a 429 error code.
     */
    public static function throttled(string $message): self
    {
        return new self($message, 429);
    }
}
