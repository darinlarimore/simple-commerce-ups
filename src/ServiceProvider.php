<?php

namespace Darinlarimore\SimpleCommerceUps;

use Darinlarimore\SimpleCommerceUps\Console\Commands\MakeUPSShippingMethod;
use Statamic\Providers\AddonServiceProvider;
use Darinlarimore\SimpleCommerceUps\Fieldtypes\PackageDimensionsFieldtype;

class ServiceProvider extends AddonServiceProvider
{
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected $commands = [
        MakeUPSShippingMethod::class,
    ];

    public function bootAddon()
    {
        PackageDimensionsFieldtype::register();
    }

    public function register()
    {
        $this->app->bind('ups', function () {
            return new \Darinlarimore\SimpleCommerceUps\Services\UPS();
        });
    }
}
