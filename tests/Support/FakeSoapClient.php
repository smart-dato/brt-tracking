<?php

namespace SmartDato\BrtTracking\Tests\Support;

use RuntimeException;
use SoapClient;

/**
 * A SoapClient that answers from canned responses instead of calling BRT.
 *
 * The parent constructor is deliberately not called: it would fetch the WSDL
 * over the network, which is the thing we are avoiding.
 */
final class FakeSoapClient extends SoapClient
{
    /** @var array<string, list<object>> */
    private array $responses;

    /** @var list<array{operation: string, payload: mixed}> */
    public array $calls = [];

    /**
     * @param  array<string, object|list<object>>  $responses  keyed by SOAP operation.
     *                                                         A list is returned one call at a time,
     *                                                         for the paginated legend endpoints.
     */
    public function __construct(array $responses)
    {
        $this->responses = array_map(
            static fn ($value) => is_array($value) ? $value : [$value],
            $responses
        );
    }

    public function __soapCall(
        string $name,
        array $args,
        ?array $options = null,
        $inputHeaders = null,
        &$outputHeaders = null
    ): mixed {
        $this->calls[] = ['operation' => $name, 'payload' => $args[0] ?? null];

        if (! isset($this->responses[$name]) || $this->responses[$name] === []) {
            throw new RuntimeException("No canned response left for SOAP operation [{$name}].");
        }

        return count($this->responses[$name]) === 1
            ? $this->responses[$name][0]
            : array_shift($this->responses[$name]);
    }
}
