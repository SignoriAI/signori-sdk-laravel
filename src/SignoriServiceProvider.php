<?php

declare(strict_types=1);

namespace Signori\Laravel;

use Illuminate\Support\ServiceProvider;
use Signori\Exceptions\SignoriException;
use Signori\Signori;

class SignoriServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/signori.php', 'signori');

        $this->app->singleton(Signori::class, function ($app) {
            $config  = $app['config']['signori'];
            $baseUrl = trim((string) ($config['base_url'] ?? ''));

            // Guarded here too: SDK releases before NEX-5674 fall back to a SaaS URL.
            if ($baseUrl === '') {
                throw new SignoriException(
                    'Signori base URL is not configured. Set SIGNORI_BASE_URL (config: signori.base_url).',
                );
            }

            return Signori::client(
                apiKey:     $config['api_key'] ?? null,
                baseUrl:    $baseUrl,
                timeout:    (int) ($config['timeout'] ?? 30),
                maxRetries: (int) ($config['max_retries'] ?? 1),
            );
        });

        $this->app->alias(Signori::class, 'signori');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/signori.php' => config_path('signori.php'),
            ], 'signori-config');
        }
    }
}
