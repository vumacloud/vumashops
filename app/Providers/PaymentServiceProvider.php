<?php

namespace App\Providers;

use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaystackGateway;
use App\Services\Payments\FlutterwaveGateway;
use App\Services\Payments\MpesaKenyaGateway;
use App\Services\Payments\MpesaTanzaniaGateway;
use App\Services\Payments\MtnMomoGateway;
use App\Services\Payments\AirtelMoneyGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager();
        });

        $this->app->alias(PaymentManager::class, 'payments');

        // Register individual gateways for direct resolution
        $this->app->bind(PaystackGateway::class, function ($app) {
            return new PaystackGateway(config('payments.gateways.paystack', []));
        });

        $this->app->bind(FlutterwaveGateway::class, function ($app) {
            return new FlutterwaveGateway(config('payments.gateways.flutterwave', []));
        });

        $this->app->bind(MpesaKenyaGateway::class, function ($app) {
            return new MpesaKenyaGateway(config('payments.gateways.mpesa_kenya', []));
        });

        $this->app->bind(MpesaTanzaniaGateway::class, function ($app) {
            return new MpesaTanzaniaGateway(config('payments.gateways.mpesa_tanzania', []));
        });

        $this->app->bind(MtnMomoGateway::class, function ($app) {
            return new MtnMomoGateway(config('payments.gateways.mtn_momo', []));
        });

        $this->app->bind(AirtelMoneyGateway::class, function ($app) {
            return new AirtelMoneyGateway(config('payments.gateways.airtel_money', []));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
