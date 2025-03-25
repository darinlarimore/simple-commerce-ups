<?php
namespace Darinlarimore\SimpleCommerceUps\Services;

use GuzzleHttp\Client;
use DVDoug\BoxPacker\Packer;
use Darinlarimore\SimpleCommerceUps\Services\ShipItem;
use Darinlarimore\SimpleCommerceUps\Services\ShipBox;
use DVDoug\BoxPacker\Rotation;
use Statamic\Facades\Blink;
use Illuminate\Validation\ValidationException;

class UPS
{
    public function checkAvailability($order, $service)
    {
        if ($this->fetchShippingRates($order,  $service) === false) {
            return false;
        }
        return true;
    }

    public function fetchShippingRates($order, $service)
    {
        // if no items in the cart, return false
        if ($order->lineItems->count() == 0) {
            return false;
        }

        if (Blink::has($this->cacheKey($order))) {
            $response = Blink::get($this->cacheKey($order));
        } else {
            $payload = $this->generatePayload($order);

            if (!$payload) {
                return false;
            }

            $response = $this->request('POST', 'api/rating/v1/Shop', [
                'json' => $payload,
            ])->RateResponse->RatedShipment;

            Blink::put($this->cacheKey($order), $response);
        }

        if ($response === null) {
            return false;
        }

        // Get the rate for the shipping service requested
        $shippingRates = collect($response)->first(function ($rate) use ($service) {
            return $rate->Service->Code == array_search($service, $this->serviceList);
        });

        if (!$shippingRates) {
            return false;
        }

        return $shippingRates->TotalCharges->MonetaryValue * 100;
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

        if ($boxes->count() == 0) {
            return false;
        }


        foreach ($boxes as $box) {
            if (config('simple-commerce-ups.unitOfMeasurement') === 'metric') {
                $dimensionsUnit = "CM";
                $weightUnit = "KG";
                $length = round($box->box->getOuterLength() / 10, 1);
                $width = round($box->box->getOuterWidth() / 10, 1);
                $height = round($box->box->getOuterDepth() / 10, 1);
                $weight = round(max(1, $box->getWeight() / 1000), 1); // UPS requires a minimum weight of 1kg
            } else {
                $dimensionsUnit = "IN";
                $weightUnit = "LBS";
                $length = round($box->box->getOuterLength() / 25.4, 1);
                $width = round($box->box->getOuterWidth() / 25.4, 1);
                $height = round($box->box->getOuterDepth() / 25.4, 1);
                $weight = round(max(1, $box->getWeight() / 453.59237), 1); // UPS requires a minimum weight of 1lb
            }

            $payload['RateRequest']['Shipment']['Package'][] = [
                'PackagingType' => [
                    'Code' => '02',
                ],
                'Dimensions' => [
                    'UnitOfMeasurement' => [
                        'Code' => $dimensionsUnit,
                    ],
                    'Length' => (string) $length,
                    'Width' => (string) $width,
                    'Height' => (string) $height,
                ],
                'PackageWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => $weightUnit,
                    ],
                    'Weight' => (string) $weight,
                ],
            ];
        }

        return $payload;
    }

    public function cacheKey($order)
    {
        return $order->id() . md5($order->shippingAddress()) . md5($order->lineItems()->pull('product'));
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
        try {
            $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);
        } catch (\Exception $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            $errorMessage = $errorResponse['response']['errors'][0]['message'] ?? $e->getMessage();
            throw ValidationException::withMessages([$errorMessage]);
        }

        return json_decode($response->getBody());
    }

    public array $boxSizes = [
        [
            'id' => 'ups-1',
            'name' => 'UPS Letter',
            'boxLength' => 318,
            'boxWidth' => 241,
            'boxHeight' => 6,
            'boxWeight' => 0,
            'maxWeight' => 227,
            'enabled' => true,
        ],
        [
            'id' => 'ups-2',
            'name' => 'Tube',
            'boxLength' => 965,
            'boxWidth' => 152,
            'boxHeight' => 152,
            'boxWeight' => 0,
            'maxWeight' => 45359,
            'enabled' => true,
        ],
        [
            'id' => 'ups-3',
            'name' => '10KG Box',
            'boxLength' => 419,
            'boxWidth' => 337,
            'boxHeight' => 273,
            'boxWeight' => 0,
            'maxWeight' => 9979,
            'enabled' => true,
        ],
        [
            'id' => 'ups-4',
            'name' => '25KG Box',
            'boxLength' => 502,
            'boxWidth' => 451,
            'boxHeight' => 335,
            'boxWeight' => 0,
            'maxWeight' => 24948,
            'enabled' => true,
        ],
        [
            'id' => 'ups-5',
            'name' => 'Small Express Box',
            'boxLength' => 330,
            'boxWidth' => 279,
            'boxHeight' => 51,
            'boxWeight' => 0,
            'maxWeight' => 45359,
            'enabled' => true,
        ],
        [
            'id' => 'ups-6',
            'name' => 'Medium Express Box',
            'boxLength' => 406,
            'boxWidth' => 279,
            'boxHeight' => 76,
            'boxWeight' => 0,
            'maxWeight' => 45359,
            'enabled' => true,
        ],
        [
            'id' => 'ups-7',
            'name' => 'Large Express Box',
            'boxLength' => 457,
            'boxWidth' => 330,
            'boxHeight' => 76,
            'boxWeight' => 0,
            'maxWeight' => 13608,
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
                outerWidth: $box['boxWidth'],
                outerLength: $box['boxLength'],
                outerDepth: $box['boxHeight'],
                emptyWeight: 0,
                innerWidth: $box['boxWidth'],
                innerLength: $box['boxLength'],
                innerDepth: $box['boxHeight'],
                maxWeight: $box['maxWeight'],
            ));
        });

        $order->lineItems->map(function ($item) use ($packer) {
            $lineItemData = \Statamic\Facades\Entry::find($item->product);
            $packageDimensions = (object) $lineItemData->get('package_dimensions');

            for ($i = 0; $i < $item->quantity; $i++) {
                if ($packageDimensions->weight == null && $packageDimensions->width == null && $packageDimensions->height == null && $packageDimensions->length == null) {
                    continue;
                }

                if ($lineItemData->get('product_type')  === 'digital') {
                    continue;
                }

                if (config('simple-commerce-ups.unitOfMeasurement') === 'metric') {
                    $packer->addItem(new ShipItem(
                        description: $item->product,
                        width: (int) ($packageDimensions->width * 10),
                        length: (int) ($packageDimensions->height * 10),
                        depth: (int) ($packageDimensions->length * 10),
                        weight: (int) ($packageDimensions->weight * 1000),
                        allowedRotation: Rotation::BestFit,
                    ));
                } else {
                    $packer->addItem(new ShipItem(
                        description: $item->product,
                        width: (int) ($packageDimensions->width * 25.4),
                        length: (int) ($packageDimensions->height * 25.4),
                        depth: (int) ($packageDimensions->length * 25.4),
                        weight: (int) ($packageDimensions->weight * 453.59237),
                        allowedRotation: Rotation::BestFit,
                    ));
                }
            }

        });

        $packedBoxes = $packer->pack();

        return $packedBoxes;

    }
}


