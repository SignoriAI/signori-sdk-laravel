<?php

declare(strict_types=1);

namespace SignVault\Laravel\Tests\Unit;

use Illuminate\Support\Facades\Event;
use SignVault\Laravel\Events\WebhookReceived;
use SignVault\Laravel\Http\Controllers\WebhookController;
use SignVault\Laravel\Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    private function makePayload(array $data = []): string
    {
        return json_encode(array_merge([
            'event' => 'document.completed',
            'data'  => ['id' => 'doc_abc123', 'status' => 'completed'],
        ], $data));
    }

    private function makeSignature(string $payload, string $secret = 'wh_test_secret'): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    private function postWebhook(string $payload, string $signature): \Illuminate\Testing\TestResponse
    {
        $this->app['router']->post('/test-webhook', WebhookController::class);

        return $this->withoutMiddleware()
            ->post('/test-webhook', [], [
                'Content-Type'           => 'application/json',
                'X-SignVault-Signature'  => $signature,
                'CONTENT'               => $payload,
            ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Use a plain string content — TestResponse reads getContent()
    }

    public function test_valid_webhook_returns_200_and_fires_event(): void
    {
        Event::fake([WebhookReceived::class]);

        $payload   = $this->makePayload();
        $signature = $this->makeSignature($payload);

        $this->app['router']->post('/test-webhook', WebhookController::class);

        $response = $this->call('POST', '/test-webhook', [], [], [], [
            'HTTP_X_SIGNVAULT_SIGNATURE' => $signature,
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);

        $response->assertStatus(200);

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->event === 'document.completed'
                && $event->data['id'] === 'doc_abc123';
        });
    }

    public function test_invalid_signature_returns_403(): void
    {
        $payload = $this->makePayload();

        $this->app['router']->post('/test-webhook', WebhookController::class);

        $response = $this->call('POST', '/test-webhook', [], [], [], [
            'HTTP_X_SIGNVAULT_SIGNATURE' => 'sha256=badsignature',
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_invalid_json_returns_400(): void
    {
        $payload   = 'not-json';
        $signature = $this->makeSignature($payload);

        $this->app['router']->post('/test-webhook', WebhookController::class);

        $response = $this->call('POST', '/test-webhook', [], [], [], [
            'HTTP_X_SIGNVAULT_SIGNATURE' => $signature,
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);

        $response->assertStatus(400);
    }

    public function test_no_secret_configured_allows_any_payload(): void
    {
        Event::fake([WebhookReceived::class]);

        $this->app['config']->set('signvault.webhook_secret', '');

        $payload = $this->makePayload();

        $this->app['router']->post('/test-webhook', WebhookController::class);

        $response = $this->call('POST', '/test-webhook', [], [], [], [
            'HTTP_X_SIGNVAULT_SIGNATURE' => '',
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);

        $response->assertStatus(200);
    }
}
