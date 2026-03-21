<?php
namespace Darinlarimore\SimpleCommerceUps\Services;

use GuzzleHttp\Client;
use DVDoug\BoxPacker\Packer;
use Darinlarimore\SimpleCommerceUps\Services\ShipItem;
use Darinlarimore\SimpleCommerceUps\Services\ShipBox;
use DVDoug\BoxPacker\Rotation;
use Statamic\Facades\Blink;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;
use Statamic\Facades\File;

class UPS
{
    protected $boxesPath = 'content/boxes.yaml';

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
                        'Address' => array_filter([
                            'City' => (string) $order->shippingAddress()->city(),
                            'StateProvinceCode' => $order->shippingAddress()->region()
                                ? (string) $order->shippingAddress()->region()['name']
                                : null,
                            'PostalCode' => (string) $order->shippingAddress()->zipCode(),
                            'CountryCode' => (string) $order->shippingAddress()->country()['iso'],
                        ]),
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
            $errorBody = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            $errorResponse = $errorBody ? json_decode($errorBody, true) : null;
            $errorMessage = $errorResponse['response']['errors'][0]['message'] ?? $e->getMessage();
            throw ValidationException::withMessages([$errorMessage]);
        }

        return json_decode($response->getBody());
    }

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

    public function getBoxes()
    {
        if (!File::exists($this->boxesPath)) {
            return collect();
        }

        $content = YAML::parse(File::get($this->boxesPath));
        return collect($content['boxes'] ?? []);
    }

    public function addCustomBox($boxData)
    {
        $boxes = $this->getBoxes();
        $boxData['id'] = 'custom-' . Str::slug($boxData['name']);
        $boxData['enabled'] = true;
        $boxes->push($boxData);

        $content = ['boxes' => $boxes->toArray()];
        File::put($this->boxesPath, YAML::dump($content));
    }

    public function deleteBox($id)
    {
        $boxes = $this->getBoxes();
        $boxes = $boxes->reject(function ($box) use ($id) {
            return $box['id'] === $id;
        });

        $content = ['boxes' => $boxes->toArray()];
        File::put($this->boxesPath, YAML::dump($content));
    }

    public function packOrder($order)
    {
        $packer = new Packer();
        $packedBoxes = collect();

        // Set the box sizes including custom boxes
        $boxes = $this->getBoxes();

        $order->lineItems->map(function ($item) use ($packer, $boxes, &$packedBoxes) {
            $lineItemData = \Statamic\Facades\Entry::find($item->product);
            $packageDimensions = (object) $lineItemData->get('package_dimensions');

            // Skip if no dimensions or digital product
            if (($packageDimensions->weight == null && $packageDimensions->width == null && $packageDimensions->height == null && $packageDimensions->length == null) ||
                $lineItemData->get('product_type') === 'digital') {
                return;
            }

            // If item needs to be packaged separately, create individual boxes
            if ($packageDimensions->package_separately) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $individualPacker = new Packer();
                    $this->addBoxesToPacker($individualPacker, $boxes);
                    $this->addItemToPacker($individualPacker, $item, $packageDimensions, 1);
                    $packedBoxes = $packedBoxes->merge($individualPacker->pack());
                }
            } else {
                $this->addItemToPacker($packer, $item, $packageDimensions, $item->quantity);
            }
        });

        // Add boxes to main packer if we're using it
        if ($boxes->count() > 0) {
            $this->addBoxesToPacker($packer, $boxes);
            $packedBoxes = $packedBoxes->merge($packer->pack());
        }

        return $packedBoxes;
    }

    protected function addBoxesToPacker($packer, $boxes)
    {
        $boxes->map(function ($box) use ($packer) {
            if (config('simple-commerce-ups.unitOfMeasurement') === 'metric') {
                $packer->addBox(new ShipBox(
                    reference: $box['name'],
                    outerWidth: (int) ($box['boxWidth'] * 10),
                    outerLength: (int) ($box['boxLength'] * 10),
                    outerDepth: (int) ($box['boxHeight'] * 10),
                    emptyWeight: (int) ($box['boxWeight'] * 1000),
                    innerWidth: (int) ($box['boxWidth'] * 10),
                    innerLength: (int) ($box['boxLength'] * 10),
                    innerDepth: (int) ($box['boxHeight'] * 10),
                    maxWeight: (int) ($box['maxWeight'] * 1000),
                ));
            } else {
                $packer->addBox(new ShipBox(
                    reference: $box['name'],
                    outerWidth: (int) ($box['boxWidth'] * 25.4),
                    outerLength: (int) ($box['boxLength'] * 25.4),
                    outerDepth: (int) ($box['boxHeight'] * 25.4),
                    emptyWeight: (int) ($box['boxWeight'] * 453.59237),
                    innerWidth: (int) ($box['boxWidth'] * 25.4),
                    innerLength: (int) ($box['boxLength'] * 25.4),
                    innerDepth: (int) ($box['boxHeight'] * 25.4),
                    maxWeight: (int) ($box['maxWeight'] * 453.59237)
                ));
            }
        });
    }

    protected function addItemToPacker($packer, $item, $packageDimensions, $quantity)
    {
        if (config('simple-commerce-ups.unitOfMeasurement') === 'metric') {
            $packer->addItem(new ShipItem(
                description: $item->product,
                width: (int) ($packageDimensions->width * 10),
                length: (int) ($packageDimensions->height * 10),
                depth: (int) ($packageDimensions->length * 10),
                weight: (int) ($packageDimensions->weight * 1000),
                allowedRotation: Rotation::BestFit,
            ), $quantity);
        } else {
            $packer->addItem(new ShipItem(
                description: $item->product,
                width: (int) ($packageDimensions->width * 25.4),
                length: (int) ($packageDimensions->height * 25.4),
                depth: (int) ($packageDimensions->length * 25.4),
                weight: (int) ($packageDimensions->weight * 453.59237),
                allowedRotation: Rotation::BestFit,
            ), $quantity);
        }
    }
}


