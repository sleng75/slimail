<?php

namespace App\Providers;

use App\Contracts\EmailServiceInterface;
use App\Services\Amazon\MockSESService;
use App\Services\Amazon\SESService;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailServiceInterface::class, function ($app) {
            // Check if mock mode is enabled
            if ($this->shouldUseMockMode()) {
                return new MockSESService();
            }

            return new SESService();
        });

        // Also register the concrete class for backward compatibility
        $this->app->singleton(SESService::class, function ($app) {
            if ($this->shouldUseMockMode()) {
                // Return mock service even when SESService is requested directly
                return new MockSESService();
            }
            return new SESService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if mock mode should be used.
     */
    protected function shouldUseMockMode(): bool
    {
        // Use mock mode if explicitly enabled
        if (config('services.ses.mock_mode', false)) {
            return true;
        }

        // Use mock mode if SES credentials are not configured
        if (empty(config('services.ses.key')) || config('services.ses.key') === 'your-ses-access-key-id') {
            return true;
        }

        if (empty(config('services.ses.secret')) || config('services.ses.secret') === 'your-ses-secret-access-key') {
            return true;
        }

        // Use mock mode in local environment by default (unless explicitly disabled)
        if (app()->environment('local') && !config('services.ses.force_production', false)) {
            return true;
        }

        return false;
    }
}
