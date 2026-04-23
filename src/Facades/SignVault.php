<?php

declare(strict_types=1);

namespace SignVault\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SignVault\Resources\ApiKeys;
use SignVault\Resources\Documents;
use SignVault\Resources\Signers;
use SignVault\Resources\Templates;
use SignVault\Resources\Webhooks;

/**
 * @method static Documents  documents()
 * @method static Signers    signers()
 * @method static Templates  templates()
 * @method static Webhooks   webhooks()
 * @method static ApiKeys    apiKeys()
 *
 * @see \SignVault\SignVault
 */
class SignVault extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SignVault\SignVault::class;
    }
}
