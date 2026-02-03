<?php

namespace App\Providers;

use App\Services\Notifications\BrevoEmailService;
use App\Services\Notifications\AfricasTalkingSmsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Brevo Email Service
        $this->app->singleton(BrevoEmailService::class, function ($app) {
            return new BrevoEmailService();
        });

        $this->app->alias(BrevoEmailService::class, 'brevo');

        // Register Africa's Talking SMS Service
        $this->app->singleton(AfricasTalkingSmsService::class, function ($app) {
            return new AfricasTalkingSmsService();
        });

        $this->app->alias(AfricasTalkingSmsService::class, 'sms');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Brevo mail transport
        $this->app->afterResolving(MailManager::class, function (MailManager $manager) {
            $manager->extend('brevo', function () {
                $apiKey = config('notifications.email.brevo.api_key');

                if (class_exists(BrevoTransportFactory::class)) {
                    $factory = new BrevoTransportFactory();
                    return $factory->create(new Dsn('brevo+api', 'default', $apiKey));
                }

                // Fallback to SMTP transport for Brevo
                return new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                    'smtp-relay.brevo.com',
                    587,
                    false
                );
            });
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            BrevoEmailService::class,
            AfricasTalkingSmsService::class,
            'brevo',
            'sms',
        ];
    }
}
