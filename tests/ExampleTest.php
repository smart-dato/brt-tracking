<?php

use SmartDato\BrtTracking\BrtTrackingClient;
use SmartDato\BrtTracking\Exceptions\BrtException;
use SmartDato\BrtTracking\Tests\Support\FakeSoapClient;

/**
 * @param  array<string, object|list<object>>  $responses
 */
function fakeBrt(array $responses): FakeSoapClient
{
    $fake = new FakeSoapClient($responses);

    BrtTrackingClient::useSoapClientFactory(fn (): FakeSoapClient => $fake);

    return $fake;
}

function soapReturn(array $payload): object
{
    return (object) ['return' => json_decode(json_encode($payload), false)];
}

beforeEach(function () {
    $this->brt = new BrtTrackingClient(['client_id' => '1234567']);
});

afterEach(fn () => BrtTrackingClient::useSoapClientFactory(null));

it('tracking by shipment id', function (string $shipmentId) {
    $fake = fakeBrt(['BRT_TrackingByBRTshipmentID' => soapReturn([
        'ESITO' => 0,
        'BOLLA' => ['DATI_SPEDIZIONE' => ['SPEDIZIONE_ID' => $shipmentId]],
        'LISTA_EVENTI' => [
            ['EVENTO' => ['ID' => 'AF', 'FILIALE' => '012', 'DATA' => '2026-09-01', 'ORA' => '09:15', 'DESCRIZIONE' => 'In transito']],
        ],
    ])]);

    $shipment = $this->brt->trackingByShipmentId($shipmentId, 2026, 'en');

    expect($shipment->events)->toHaveCount(1)
        ->and($shipment->events[0]->description)->toBe('In transito');

    expect($fake->calls[0]['operation'])->toBe('BRT_TrackingByBRTshipmentID')
        ->and($fake->calls[0]['payload']['arg0']['SPEDIZIONE_BRT_ID'])->toBe($shipmentId)
        ->and($fake->calls[0]['payload']['arg0']['SPEDIZIONE_ANNO'])->toBe(2026)
        ->and($fake->calls[0]['payload']['arg0']['LINGUA_ISO639_ALPHA2'])->toBe('en');
})->with(['00000000000']);

it('Get ShipmentId by RMN', function (string $reference) {
    $fake = fakeBrt(['GetIdSpedizioneByRMN' => soapReturn([
        'ESITO' => 0,
        'SPEDIZIONE_ANNO' => 2026,
        'SPEDIZIONE_ID' => '00000000000',
    ])]);

    $shipment = $this->brt->getShipmentIdByRMN($reference);

    expect($shipment->id)->toBe('00000000000')
        ->and($shipment->year)->toBe(2026)
        ->and($fake->calls[0]['payload']['arg0']['RIFERIMENTO_MITTENTE_NUMERICO'])->toBe($reference);
})->with(['123456789']);

it('Get ShipmentId by RMA', function (string $reference) {
    $fake = fakeBrt(['GetIdSpedizioneByRMA' => soapReturn([
        'ESITO' => 0,
        'SPEDIZIONE_ANNO' => 2026,
        'SPEDIZIONE_ID' => '00000000000',
    ])]);

    $shipment = $this->brt->getShipmentIdByRMA($reference);

    expect($shipment->id)->toBe('00000000000')
        ->and($fake->calls[0]['payload']['arg0']['RIFERIMENTO_MITTENTE_ALFABETICO'])->toBe($reference);
})->with(['OLP000000000000']);

it('Get ShipmentId by Parcel', function (string $parcelId) {
    $fake = fakeBrt(['GetIdSpedizioneByIdCollo' => soapReturn([
        'ESITO' => 0,
        'SPEDIZIONE_ANNO' => 2026,
        'SPEDIZIONE_ID' => '00000000000',
    ])]);

    $shipment = $this->brt->getShipmentIdByParcel($parcelId);

    expect($shipment->id)->toBe('00000000000')
        ->and($fake->calls[0]['payload']['arg0']['COLLO_ID'])->toBe($parcelId);
})->with(['CC000000000000']);

it('surfaces a BRT error code as an exception', function () {
    fakeBrt(['GetIdSpedizioneByRMN' => soapReturn(['ESITO' => -21])]);

    expect(fn () => $this->brt->getShipmentIdByRMN('123456789'))
        ->toThrow(BrtException::class, 'Missing client id');
});

it('Get Legenda Esiti', function () {
    // The endpoint pages in batches and signals completion with ESITO 100.
    fakeBrt(['GetLegendaEsiti' => [
        soapReturn(['ESITO' => 0, 'LEGENDA' => [['ID' => 1, 'TESTO1' => 'Consegnata', 'TESTO2' => 'Delivered']]]),
        soapReturn(['ESITO' => 100, 'LEGENDA' => [['ID' => 2, 'TESTO1' => 'In transito', 'TESTO2' => 'In transit']]]),
    ]]);

    $esiti = $this->brt->getLegendaEsiti('en');

    expect($esiti)->toHaveCount(2)
        ->and($esiti[0])->toBe(['id' => 1, 'text1' => 'Consegnata', 'text2' => 'Delivered'])
        ->and($esiti[1]['text1'])->toBe('In transito');
});

it('Get Legenda Eventi', function () {
    // Unlike the esiti legend, this endpoint returns ID and DESCRIZIONE.
    fakeBrt(['GetLegendaEventi' => [
        soapReturn(['ESITO' => 0, 'LEGENDA' => [['ID' => 'AF', 'DESCRIZIONE' => 'Arrivo filiale']]]),
        soapReturn(['ESITO' => 100, 'LEGENDA' => []]),
    ]]);

    $eventi = $this->brt->getLegendaEventi('en');

    expect($eventi)->toHaveCount(1)
        ->and($eventi[0])->toBe(['id' => 'AF', 'description' => 'Arrivo filiale']);
});
