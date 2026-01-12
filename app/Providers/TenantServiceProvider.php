<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * TenantServiceProvider
 * 
 * Registers the TenantContext singleton and related services.
 */
class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TenantContext as a singleton
        $this->app->singleton(TenantContext::class, function ($app) {
            return new TenantContext();
        });

        // Create a global helper alias
        $this->app->alias(TenantContext::class, 'tenant');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/twinx.php' => config_path('twinx.php'),
        ], 'twinx-config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/twinx.php',
            'twinx'
        );
    }
}
