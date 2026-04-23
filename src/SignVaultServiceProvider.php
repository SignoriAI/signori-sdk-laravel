<?php

declare(strict_types=1);

namespace SignVault\Laravel;

use Illuminate\Support\ServiceProvider;
use SignVault\SignVault;

class SignVaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/signvault.php', 'signvault');

        $this->app->singleton(SignVault::class, function ($app) {
            $config = $app['config']['signvault'];

            return SignVault::client(
                apiKey:     $config['api_key'] ?? null,
                baseUrl:    $config['base_url'] ?? null,
                timeout:    (int) ($config['timeout'] ?? 30),
                maxRetries: (int) ($config['max_retries'] ?? 1),
            );
        });

        $this->app->alias(SignVault::class, 'signvault');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/signvault.php' => config_path('signvault.php'),
            ], 'signvault-config');
        }
    }
}
