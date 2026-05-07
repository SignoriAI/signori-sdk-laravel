<?php

declare(strict_types=1);

namespace Signori\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Signori\Laravel\SignoriServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SignoriServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Signori' => \Signori\Laravel\Facades\Signori::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('signori.api_key', 'sv_test_key_for_unit_tests');
        $app['config']->set('signori.base_url', 'https://api.signori.test');
        $app['config']->set('signori.timeout', 10);
        $app['config']->set('signori.max_retries', 0);
        $app['config']->set('signori.webhook_secret', 'wh_test_secret');
    }
}
