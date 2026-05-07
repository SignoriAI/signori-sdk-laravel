<?php

declare(strict_types=1);

namespace Signori\Laravel\Tests\Unit;

use Signori\Laravel\Tests\TestCase;
use Signori\Signori;

class ServiceProviderTest extends TestCase
{
    public function test_resolves_signori_from_container(): void
    {
        $client = $this->app->make(Signori::class);

        $this->assertInstanceOf(Signori::class, $client);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $a = $this->app->make(Signori::class);
        $b = $this->app->make(Signori::class);

        $this->assertSame($a, $b);
    }

    public function test_alias_resolves(): void
    {
        $client = $this->app->make('signori');

        $this->assertInstanceOf(Signori::class, $client);
    }

    public function test_config_is_published_with_expected_keys(): void
    {
        $config = config('signori');

        $this->assertArrayHasKey('api_key', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('max_retries', $config);
        $this->assertArrayHasKey('webhook_secret', $config);
    }

    public function test_missing_api_key_throws(): void
    {
        $this->expectException(\Signori\Exceptions\SignoriException::class);

        $this->app['config']->set('signori.api_key', null);
        // Force a fresh (non-singleton) resolution to trigger __construct validation.
        $this->app->forgetInstance(Signori::class);
        $this->app->make(Signori::class);
    }
}
