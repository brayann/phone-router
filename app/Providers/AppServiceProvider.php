<?php

namespace App\Providers;

use App\Vendors\Carriers\CarrierInterface;
use App\Vendors\Carriers\Twilio\TwilioClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CarrierInterface::class, TwilioClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
