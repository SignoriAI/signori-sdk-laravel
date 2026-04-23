<?php

declare(strict_types=1);

namespace SignVault\Laravel\Tests\Unit;

use SignVault\Laravel\Facades\SignVault;
use SignVault\Laravel\Tests\TestCase;
use SignVault\Resources\Documents;
use SignVault\Resources\Signers;
use SignVault\Resources\Templates;
use SignVault\Resources\Webhooks;
use SignVault\Resources\ApiKeys;

class FacadeTest extends TestCase
{
    public function test_facade_exposes_documents_resource(): void
    {
        $this->assertInstanceOf(Documents::class, SignVault::getFacadeRoot()->documents);
    }

    public function test_facade_exposes_signers_resource(): void
    {
        $this->assertInstanceOf(Signers::class, SignVault::getFacadeRoot()->signers);
    }

    public function test_facade_exposes_templates_resource(): void
    {
        $this->assertInstanceOf(Templates::class, SignVault::getFacadeRoot()->templates);
    }

    public function test_facade_exposes_webhooks_resource(): void
    {
        $this->assertInstanceOf(Webhooks::class, SignVault::getFacadeRoot()->webhooks);
    }

    public function test_facade_exposes_api_keys_resource(): void
    {
        $this->assertInstanceOf(ApiKeys::class, SignVault::getFacadeRoot()->apiKeys);
    }
}
