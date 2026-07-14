<?php

namespace Darinlarimore\SimpleCommerceUps;

use Darinlarimore\SimpleCommerceUps\Console\Commands\MakeUPSShippingMethod;
use Statamic\Providers\AddonServiceProvider;
use Darinlarimore\SimpleCommerceUps\Fieldtypes\PackageDimensionsFieldtype;
use Statamic\Facades\CP\Nav;
use Darinlarimore\SimpleCommerceUps\Http\Controllers\BoxController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class ServiceProvider extends AddonServiceProvider
{
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
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

    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'simple-commerce-ups');

        if (File::isDirectory(base_path('content')) && ! File::exists(base_path('content/boxes.yaml'))) {
            File::copy(__DIR__.'/../content/boxes.yaml', base_path('content/boxes.yaml'));
        }

        Nav::extend(function ($nav) {
            $nav->content('UPS Boxes')
                ->section('Simple Commerce')
                ->route('boxes.index')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>');
        });

        Route::group([
            'prefix' => config('statamic.cp.route', 'cp'),
            'middleware' => ['web', 'statamic.cp', 'statamic.cp.authenticated'],
            'as' => 'statamic.cp.',
        ], function () {
            Route::prefix('boxes')->group(function () {
                Route::get('/', [BoxController::class, 'index'])->name('boxes.index');
                Route::post('/', [BoxController::class, 'store'])->name('boxes.store');
                Route::delete('/{id}', [BoxController::class, 'destroy'])->name('boxes.destroy');
            });
        });
    }
}
