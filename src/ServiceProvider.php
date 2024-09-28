<?php

namespace Darinlarimore\SimpleCommerceUps;

use Darinlarimore\SimpleCommerceUps\Console\Commands\MakeUPSShippingMethod;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{

    protected $commands = [
        MakeUPSShippingMethod::class,
    ];

    public function bootAddon()
    {
    }

    public function register()
    {
        $this->app->bind('ups', function () {
            return new \Darinlarimore\SimpleCommerceUps\Services\UPS();
        });
    }
}
