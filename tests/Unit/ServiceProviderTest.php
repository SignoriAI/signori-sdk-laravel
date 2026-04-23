<?php

declare(strict_types=1);

namespace SignVault\Laravel\Tests\Unit;

use SignVault\Laravel\Tests\TestCase;
use SignVault\SignVault;

class ServiceProviderTest extends TestCase
{
    public function test_resolves_signvault_from_container(): void
    {
        $client = $this->app->make(SignVault::class);

        $this->assertInstanceOf(SignVault::class, $client);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $a = $this->app->make(SignVault::class);
        $b = $this->app->make(SignVault::class);

        $this->assertSame($a, $b);
    }

    public function test_alias_resolves(): void
    {
        $client = $this->app->make('signvault');

        $this->assertInstanceOf(SignVault::class, $client);
    }

    public function test_config_is_published_with_expected_keys(): void
    {
        $config = config('signvault');

        $this->assertArrayHasKey('api_key', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('max_retries', $config);
        $this->assertArrayHasKey('webhook_secret', $config);
    }

    public function test_missing_api_key_throws(): void
    {
        $this->expectException(\SignVault\Exceptions\SignVaultException::class);

        $this->app['config']->set('signvault.api_key', null);
        // Force a fresh (non-singleton) resolution to trigger __construct validation.
        $this->app->forgetInstance(SignVault::class);
        $this->app->make(SignVault::class);
    }
}
