<?php

namespace Darinlarimore\SimpleCommerceUps\Fieldtypes;

use Statamic\Fields\Fieldtype;

class PackageDimensionsFieldtype extends Fieldtype
{
    protected $icon = 'box';

    protected static $handle = 'package_dimensions';

    protected function configFieldItems(): array
    {
        return [
            'metric' => [
                'display' => 'Use Metric',
                'instructions' => 'Use metric measurements (cm) instead of imperial (inches)',
                'type' => 'toggle',
                'default' => false,
            ],
        ];
    }

    public function preProcess($data)
    {
        return $data ?? [
            'package_type' => null,
            'package_separately' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'weight' => null,
        ];
    }

    public function augment($data)
    {
      return $data;
    }

    public function component(): string
    {
      return 'package-dimensions';
    }
}
