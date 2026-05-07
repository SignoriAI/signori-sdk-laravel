<?php

declare(strict_types=1);

namespace Signori\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Signori\Resources\ApiKeys;
use Signori\Resources\Documents;
use Signori\Resources\Signers;
use Signori\Resources\Templates;
use Signori\Resources\Webhooks;

/**
 * @method static Documents  documents()
 * @method static Signers    signers()
 * @method static Templates  templates()
 * @method static Webhooks   webhooks()
 * @method static ApiKeys    apiKeys()
 *
 * @see \Signori\Signori
 */
class Signori extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Signori\Signori::class;
    }
}
