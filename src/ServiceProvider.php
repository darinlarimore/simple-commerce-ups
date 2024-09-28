<?php

namespace Darinlarimore\SimpleCommerceUps;

use Darinlarimore\SimpleCommerceUps\Console\Commands\MakeUPSShippingMethod;
use Statamic\Providers\AddonServiceProvider;
use Darinlarimore\SimpleCommerceUps\Fieldtypes\UPSMethod;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish --tag=simple-commerce-ups-config');
        });
    }

    public function register()
    {
        $this->app->bind('ups', function () {
            return new \Darinlarimore\SimpleCommerceUps\Services\UPS();
        });
    }
}
