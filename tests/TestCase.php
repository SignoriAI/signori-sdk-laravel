<?php

declare(strict_types=1);

namespace SignVault\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SignVault\Laravel\SignVaultServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SignVaultServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SignVault' => \SignVault\Laravel\Facades\SignVault::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('signvault.api_key', 'sv_test_key_for_unit_tests');
        $app['config']->set('signvault.base_url', 'https://api.signvault.test');
        $app['config']->set('signvault.timeout', 10);
        $app['config']->set('signvault.max_retries', 0);
        $app['config']->set('signvault.webhook_secret', 'wh_test_secret');
    }
}
