<?php

use Darinlarimore\SimpleCommerceUps\Services\UPS;

test('Test Package Sizes', function () {
    $ups = new UPS();
    expect($ups->getBoxes())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

test('Test pickup code array', function () {
    $ups = new UPS();
    // check that pickup codes are returned
    $this->assertIsArray($ups->pickupCodes);
});

test('Test service list array', function () {
    $ups = new UPS();
    // check that service codes are returned
    $this->assertIsArray($ups->serviceList);
});
