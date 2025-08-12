<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailVerificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService();
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
