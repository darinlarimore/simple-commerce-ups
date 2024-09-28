<?php

use Darinlarimore\SimpleCommerceUps\Services\UPS;

test('Test Package Sizes', function () {
    $ups = new UPS();
    // check that box sizes are returned
    $this->assertIsArray($ups->boxSizes);
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
