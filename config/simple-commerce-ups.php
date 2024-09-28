<?php

return [
    // UPS Account Number and API credentials - these can be created https://developer.ups.com/ see the README for more information
    'accountNumber' => env('UPS_ACCOUNT_NUMBER'),
    'clientId' => env('UPS_CLIENT_ID'),
    'clientSecret' => env('UPS_CLIENT_SECRET'),

    'useTestEndpoint' => env('UPS_USE_TEST_ENDPOINT'), // true or false

    // Ship From Address
    'shipFromPostalCode' => env('UPS_SHIP_FROM_POSTAL_CODE'), // Example: 46202
    'shipFromCountryCode' => env('UPS_SHIP_FROM_COUNTRY_CODE'), // Example: US
    'shipFromCity' => env('UPS_SHIP_FROM_CITY'), // Example: Indanapolis
    'shipFromStateCode' => env('UPS_SHIP_FROM_STATE_CODE'), // Example: IN

    'pickupType' => env('UPS_PICKUP_TYPE'), // Daily Pickup, Customer Counter, One Time Pickup, On Call Air, Letter Center, Air Service Center

    'unitOfMeasurement' => env('UPS_UNIT_OF_MEASUREMENT'), // IN or CM
    'weightUnitOfMeasurement' => env('UPS_WEIGHT_UNIT_OF_MEASUREMENT'), // LBS or KGS
];
