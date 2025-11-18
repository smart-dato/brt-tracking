<?php

use SmartDato\BrtTracking\Facades\BrtTracking;

beforeEach(function () {
    // Ensure the client ID is set in the environment
    config(['brt-tracking.client_id' => '1234567890']);
});

it('tracking by shipment id', function ($shipmentId) {
    $events = BrtTracking::trackingByShipmentId($shipmentId, 2025, 'en');
    dump($events);
})->with(['12345678901234567890'])->wip();

it('Get ShipmentId by RMN', function ($reference) {
    $shipmentId = BrtTracking::getShipmentIdByRMN($reference);
    dump($shipmentId);
})->with(['12345678901234567890'])->wip();

it('Get ShipmentId by RMA', function ($reference) {
    $shipmentId = BrtTracking::getShipmentIdByRMA($reference);
    dump($shipmentId);
})->with(['12345678901234567890'])->wip();

it('Get ShipmentId by Parcel', function ($parcelId) {
    $shipmentId = BrtTracking::getShipmentIdByParcel($parcelId);
    dump($shipmentId);
})->with(['12345678901234567890'])->wip();

it('Get Legenda Esiti', function () {
    $esiti = BrtTracking::getLegendaEsiti();
    dump($esiti);
})->wip();

it('Get Legenda Eventi', function () {
    $eventi = BrtTracking::getLegendaEventi();
    dump($eventi);
})->wip();

it('example', function () {

    $client = new \SmartDato\BrtTracking\BrtTrackingClient(
        [
            'clientId' => '1234567890',
        ]
    );
    $eventi = $client->getLegendaEsiti();
    dump($eventi);
})->wip();
