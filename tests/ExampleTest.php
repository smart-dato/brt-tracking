<?php

use SmartDato\BrtTracking\BrtTrackingClient;

beforeEach(function () {
    $this->brt = new BrtTrackingClient(
        ['client_id' => '0000000']
    );
});

it('tracking by shipment id', function ($shipmentId) {
    try {
        $events = $this->brt->trackingByShipmentId($shipmentId, now()->year, 'en');
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect(true)->toBeTrue();
})->with(['00000000000'])->wip();

it('Get ShipmentId by RMN', function ($reference) {
    try {
        $shipmentId = $this->brt->getShipmentIdByRMN($reference);
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect(true)->toBeTrue();
})->with(['123456789'])->wip();

it('Get ShipmentId by RMA', function ($reference) {
    try {
        $shipment = $this->brt->getShipmentIdByRMA($reference);
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect($shipment->id)->toBe('00000000000');
})->with(['OLP000000000000']);

it('Get ShipmentId by Parcel', function ($parcelId) {
    try {
        $shipment = $this->brt->getShipmentIdByParcel($parcelId);
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect($shipment->id)->toBe('00000000000');
})->with(['CC000000000000']);

it('Get Legenda Esiti', function () {
    try {
        $esiti = $this->brt->getLegendaEsiti();
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect(true)->toBeTrue();
});

it('Get Legenda Eventi', function () {
    try {
        $eventi = $this->brt->getLegendaEventi();
    } catch (SoapFault $e) {
        $this->fail($e->getMessage());
    }
    expect(true)->toBeTrue();
});
