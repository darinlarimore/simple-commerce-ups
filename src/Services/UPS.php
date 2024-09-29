<?php
namespace Darinlarimore\SimpleCommerceUps\Services;

use GuzzleHttp\Client;
use DVDoug\BoxPacker\Packer;
use Darinlarimore\SimpleCommerceUps\Services\ShipItem;
use Darinlarimore\SimpleCommerceUps\Services\ShipBox;
use DVDoug\BoxPacker\Rotation;
use Statamic\Facades\Blink;

class UPS
{
    public function fetchShippingRates($order, $service)
    {
        // if no items in the cart, return null
        if ($order->lineItems->count() == 0) {
            return null;
        }

        if (Blink::has($this->cacheKey($order))) {
            $payload = Blink::get($this->cacheKey($order));
        } else {
            $payload = $this->generatePayload($order);
            Blink::put($this->cacheKey($order), $payload);
        }

        $response = $this->request('POST', 'api/rating/v1/Shop', [
            'json' => $payload,
        ]);

        // Get the rate for the shipping service requested
        $response = collect($response->RateResponse->RatedShipment)->first(function ($rate) use ($service) {
            return $rate->Service->Code == array_search($service, $this->serviceList);
        });


        return $response->TotalCharges->MonetaryValue * 100;
    }

    public function generatePayload($order)
    {
        $payload = [
            'RateRequest' => [
                'PickupType' => [
                    'Code' => $this->pickupCodes[config('simple-commerce-ups.pickupType')],
                ],
                'Shipment' => [
                    'Shipper' => [
                        'Address' => [
                            'City' => (string) config('simple-commerce-ups.shipFromCity'),
                            'StateProvinceCode' => (string) config('simple-commerce-ups.shipFromStateCode'),
                            'PostalCode' => (string) config('simple-commerce-ups.shipFromPostalCode'),
                            'CountryCode' => (string) config('simple-commerce-ups.shipFromCountryCode'),
                        ],
                    ],
                    'ShipFrom' => [
                        'Address' => [
                            'City' => (string) config('simple-commerce-ups.shipFromCity'),
                            'StateProvinceCode' => (string) config('simple-commerce-ups.shipFromStateCode'),
                            'PostalCode' => (string) config('simple-commerce-ups.shipFromPostalCode'),
                            'CountryCode' => (string) config('simple-commerce-ups.shipFromCountryCode'),
                        ],
                    ],
                    'ShipTo' => [
                        'Address' => [
                            'City' => (string) $order->shippingAddress()->city(),
                            'StateProvinceCode' => (string) $order->shippingAddress()->region()['name'],
                            'PostalCode' => (string) $order->shippingAddress()->zipCode(),
                            'CountryCode' => (string) $order->shippingAddress()->country()['iso'],
                        ],
                    ],
                ],
            ],
        ];

        if (config('simple-commerce-ups.accountNumber')) {
            $payload['RateRequest']['Shipment']['Shipper']['ShipperNumber'] = config('simple-commerce-ups.accountNumber');

            $payload['RateRequest']['Shipment']['ShipmentRatingOptions'] = [
                'NegotiatedRatesIndicator' => 'Y',
            ];

            $payload['RateRequest']['Shipment']['PaymentDetails'] = [
                'ShipmentCharge' => [
                    'Type' => '01',
                    'BillShipper' => [
                        'AccountNumber' => config('simple-commerce-ups.accountNumber'),
                    ],
                ],
            ];
        }

        $boxes = $this->packOrder($order);

        foreach ($boxes as $box) {
            $payload['RateRequest']['Shipment']['Package'][] = [
                'PackagingType' => [
                    'Code' => '02',
                ],
                'Dimensions' => [
                    'UnitOfMeasurement' => [
                        'Code' => config('simple-commerce-ups.unitOfMeasurement') ?? 'IN',
                    ],
                    'Length' => (string) $box->box->getOuterLength(),
                    'Width' => (string) $box->box->getOuterWidth(),
                    'Height' => (string) $box->box->getOuterDepth(),
                ],
                'PackageWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => config('simple-commerce-ups.weightUnitOfMeasurement') ?? 'LBS',
                    ],
                    'Weight' => (string) $box->getWeight(),
                ],
            ];
        }

        return $payload;
    }

    public function cacheKey($order)
    {
        return $order->id() . md5($order->shippingAddress()) . md5($order->lineItems());
    }

    public function getClient()
    {
        $url = 'https://onlinetools.ups.com/';

        if (config('simple-commerce-ups.useTestEndpoint')) {
            $url = 'https://wwwcie.ups.com/';
        }

        // Fetch an access token first using guzzle post request
        $authResponse = new Client([
            'base_uri' => $url,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'x-merchant-id' => config('simple-commerce-ups.accountNumber'),
            ],
            'auth' => [
                config('simple-commerce-ups.clientId'),
                config('simple-commerce-ups.clientSecret'),
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ]) ;
        $authResponse = json_decode($authResponse->post('security/v1/oauth/token')->getBody()->getContents());

        return new Client([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Bearer ' . $authResponse->access_token ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function request(string $method, string $uri, array $options = [])
    {
        $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);;

        return json_decode($response->getBody());
    }

    public array $boxSizes = [
        [
            'id' => 'ups-1',
            'name' => 'UPS Letter',
            'boxLength' => 12.5,
            'boxWidth' => 9.5,
            'boxHeight' => 0.25,
            'boxWeight' => 0,
            'maxWeight' => 0.5,
            'enabled' => true,
        ],
        [
            'id' => 'ups-2',
            'name' => 'Tube',
            'boxLength' => 38,
            'boxWidth' => 6,
            'boxHeight' => 6,
            'boxWeight' => 0,
            'maxWeight' => 100,
            'enabled' => true,
        ],
        [
            'id' => 'ups-3',
            'name' => '10KG Box',
            'boxLength' => 16.5,
            'boxWidth' => 13.25,
            'boxHeight' => 10.75,
            'boxWeight' => 0,
            'maxWeight' => 22,
            'enabled' => true,
        ],
        [
            'id' => 'ups-4',
            'name' => '25KG Box',
            'boxLength' => 19.75,
            'boxWidth' => 17.75,
            'boxHeight' => 13.2,
            'boxWeight' => 0,
            'maxWeight' => 55,
            'enabled' => true,
        ],
        [
            'id' => 'ups-5',
            'name' => 'Small Express Box',
            'boxLength' => 13,
            'boxWidth' => 11,
            'boxHeight' => 2,
            'boxWeight' => 0,
            'maxWeight' => 100,
            'enabled' => true,
        ],
        [
            'id' => 'ups-6',
            'name' => 'Medium Express Box',
            'boxLength' => 16,
            'boxWidth' => 11,
            'boxHeight' => 3,
            'boxWeight' => 0,
            'maxWeight' => 100,
            'enabled' => true,
        ],
        [
            'id' => 'ups-7',
            'name' => 'Large Express Box',
            'boxLength' => 18,
            'boxWidth' => 13,
            'boxHeight' => 3,
            'boxWeight' => 0,
            'maxWeight' => 30,
            'enabled' => true,
        ],
    ];

    public array $pickupCodes = [
        'Daily Pickup' => '01',
        'Customer Counter' => '03',
        'One Time Pickup' => '06',
        'On Call Air' => '07',
        'Letter Center' => '19',
        'Air Service Center' => '20',
    ];

    public array $serviceList = [
        '01'    => 'UPS Next Day Air',
        '02'    => 'UPS 2nd Day Air',
        '03'    => 'UPS Ground',
        '07'    => 'UPS Worldwide Express',
        '08'    => 'UPS Worldwide Expedited',
        '11'    => 'UPS Standard',
        '12'    => 'UPS 3 Day Select',
        '13'    => 'UPS Next Day Air Saver',
        '14'    => 'UPS Next Day Air Early A.M.',
        '54'    => 'UPS Worldwide Express Plus',
        '59'    => 'UPS 2nd Day Air A.M.',
        '65'    => 'UPS Saver',
        '82'    => 'UPS Today Standard',
        '83'    => 'UPS Today Dedicated Courier',
        '84'    => 'UPS Today Intercity',
        '85'    => 'UPS Today Express',
        '86'    => 'UPS Today Express Saver'
    ];

    public function packOrder($order)
    {
        $packer = new Packer();

        // Set the box sizes
        collect($this->boxSizes)->map(function ($box) use ($packer) {
            $packer->addBox(new ShipBox(
                reference: $box['name'],
                outerWidth: (int) $box['boxWidth'],
                outerLength: (int) $box['boxLength'],
                outerDepth: (int) $box['boxHeight'],
                emptyWeight: 0,
                innerWidth: (int) $box['boxWidth'],
                innerLength: (int) $box['boxLength'],
                innerDepth: (int) $box['boxHeight'],
                maxWeight: (int) $box['maxWeight'],
            ));
        });

        $order->lineItems->map(function ($item) use ($packer) {
            $lineItemData = $item->product->data;
            for ($i = 0; $i < $item->quantity; $i++) {
                $packer->addItem(new ShipItem(
                    description: $item->product->id,
                    width: (int) $lineItemData->get('width'),
                    length: (int) $lineItemData->get('height'),
                    depth: (int) $lineItemData->get('depth'),
                    weight: (int) $lineItemData->get('weight'),
                    allowedRotation: Rotation::BestFit,
                ));
            }

        });

        $packedBoxes = $packer->pack();

        return $packedBoxes;

    }
}


