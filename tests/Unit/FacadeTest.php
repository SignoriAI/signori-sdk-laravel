<?php

declare(strict_types=1);

namespace Signori\Laravel\Tests\Unit;

use Signori\Laravel\Facades\Signori;
use Signori\Laravel\Tests\TestCase;
use Signori\Resources\Documents;
use Signori\Resources\Signers;
use Signori\Resources\Templates;
use Signori\Resources\Webhooks;
use Signori\Resources\ApiKeys;

class FacadeTest extends TestCase
{
    public function test_facade_exposes_documents_resource(): void
    {
        $this->assertInstanceOf(Documents::class, Signori::getFacadeRoot()->documents);
    }

    public function test_facade_exposes_signers_resource(): void
    {
        $this->assertInstanceOf(Signers::class, Signori::getFacadeRoot()->signers);
    }

    public function test_facade_exposes_templates_resource(): void
    {
        $this->assertInstanceOf(Templates::class, Signori::getFacadeRoot()->templates);
    }

    public function test_facade_exposes_webhooks_resource(): void
    {
        $this->assertInstanceOf(Webhooks::class, Signori::getFacadeRoot()->webhooks);
    }

    public function test_facade_exposes_api_keys_resource(): void
    {
        $this->assertInstanceOf(ApiKeys::class, Signori::getFacadeRoot()->apiKeys);
    }
}
