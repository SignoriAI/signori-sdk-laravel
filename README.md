# SignVault Laravel SDK

Official Laravel integration for the [SignVault](https://signvault.com) e-signature API.  
Wraps [`signvault/signvault-php`](https://github.com/signvault/signvault-sdk-php) with a service provider, Facade, webhook controller, and signature middleware.

---

## Requirements

| | Minimum |
|---|---|
| PHP | 8.1 |
| Laravel | 10 or 11 |
| signvault/signvault-php | ^1.0 |

---

## Installation

```bash
composer require signvault/laravel-sdk
```

Laravel's package auto-discovery registers the service provider and `SignVault` alias automatically.

Publish the config file:

```bash
php artisan vendor:publish --tag=signvault-config
```

Add your credentials to `.env`:

```env
SIGNVAULT_API_KEY=sv_live_your_key_here
SIGNVAULT_WEBHOOK_SECRET=your_webhook_secret_here
```

---

## Usage

### Via dependency injection

```php
use SignVault\SignVault;

class ContractService
{
    public function __construct(private readonly SignVault $signVault) {}

    public function sendForSigning(string $path, string $title, array $signers): string
    {
        $doc = $this->signVault->documents->upload($path, $title);
        $this->signVault->documents->send($doc->id, $signers);
        return $doc->id;
    }
}
```

### Via Facade

```php
use SignVault\Laravel\Facades\SignVault;

$client = SignVault::getFacadeRoot();   // returns the bound \SignVault\SignVault instance

$doc = $client->documents->upload(storage_path('app/contract.pdf'), 'NDA');

$client->documents->send($doc->id, [
    ['email' => 'alice@example.com', 'full_name' => 'Alice Smith', 'role' => 'signer'],
]);
```

### Documents

```php
// Upload
$doc = $signVault->documents->upload('/path/to/file.pdf', 'Contract', 'contract');

// List with filters
$page = $signVault->documents->list(['status' => 'pending', 'limit' => 25]);
foreach ($page->items as $doc) {
    echo "{$doc->id} {$doc->title} {$doc->status}\n";
}

// Get single
$doc = $signVault->documents->get('doc_abc123');

// Send for signing
$signVault->documents->send($doc->id, [
    ['email' => 'alice@example.com', 'full_name' => 'Alice Smith', 'role' => 'signer'],
    ['email' => 'bob@example.com',   'full_name' => 'Bob Jones',   'role' => 'approver'],
], message: 'Please review and sign.', expiryDays: 14);

// Download signed PDF
$bytes = $signVault->documents->downloadSigned($doc->id);

// Void
$signVault->documents->void($doc->id, 'Sent to wrong recipient');

// Audit trail
$audit = $signVault->documents->auditTrail($doc->id);
```

### Templates

```php
$doc = $signVault->templates->createDocument(
    templateId: 'tpl_abc123',
    fieldValues: ['party_name' => 'Acme Corp', 'effective_date' => '2026-01-01'],
    signers: [['email' => 'ceo@acme.com', 'full_name' => 'Jane CEO']],
);
```

### API Keys & Webhooks

```php
// API Keys
$key  = $signVault->apiKeys->create('CI deploy key');
$page = $signVault->apiKeys->list();
$signVault->apiKeys->delete($key->id);

// Webhooks
$hook = $signVault->webhooks->create('https://example.com/hooks/sv', ['document.completed']);
$signVault->webhooks->update($hook->id, ['is_active' => false]);
$signVault->webhooks->delete($hook->id);
```

---

## Webhooks

### Option A — Drop-in controller

Register a route and exclude it from CSRF:

```php
// routes/api.php (already CSRF-exempt) or routes/web.php
Route::post('/webhooks/signvault', \SignVault\Laravel\Http\Controllers\WebhookController::class);
```

If using `routes/web.php`, add the path to `VerifyCsrfToken::$except`.

The controller:
1. Verifies the HMAC-SHA256 signature against `SIGNVAULT_WEBHOOK_SECRET` (skips if secret is empty).
2. Fires `SignVault\Laravel\Events\WebhookReceived` on success.

Listen for the event:

```php
// EventServiceProvider
use SignVault\Laravel\Events\WebhookReceived;

protected $listen = [
    WebhookReceived::class => [
        App\Listeners\HandleSignVaultWebhook::class,
    ],
];
```

```php
// App\Listeners\HandleSignVaultWebhook
public function handle(WebhookReceived $event): void
{
    match ($event->event) {
        'document.completed' => $this->onCompleted($event->data),
        'signer.signed'      => $this->onSigned($event->data),
        default              => null,
    };
}
```

### Option B — Middleware on your own handler

```php
Route::post('/webhooks/signvault', MyWebhookHandler::class)
    ->middleware(\SignVault\Laravel\Http\Middleware\VerifyWebhookSignature::class)
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### Manual verification

```php
use SignVault\Resources\Webhooks;

$payload   = $request->getContent();
$signature = $request->header('X-SignVault-Signature', '');

if (! Webhooks::verify($payload, $signature, config('signvault.webhook_secret'))) {
    abort(403);
}

$event = Webhooks::constructEvent($payload);
// $event['event'] — e.g. "document.completed"
// $event['data']  — decoded payload
```

---

## Error handling

```php
use SignVault\Exceptions\AuthException;
use SignVault\Exceptions\NotFoundException;
use SignVault\Exceptions\ValidationException;
use SignVault\Exceptions\RateLimitException;
use SignVault\Exceptions\ApiException;

try {
    $doc = $signVault->documents->get('doc_xyz');
} catch (NotFoundException $e) {
    abort(404, $e->getMessage());
} catch (AuthException) {
    abort(401, 'Invalid API key.');
} catch (ValidationException $e) {
    abort(422, $e->getMessage());
} catch (RateLimitException) {
    abort(429, 'Rate limited — try again shortly.');
} catch (ApiException $e) {
    // $e->requestId is available for support tickets
    abort(500, $e->getMessage());
}
```

---

## Configuration reference

| Key | Env var | Default | Description |
|---|---|---|---|
| `api_key` | `SIGNVAULT_API_KEY` | — | Bearer token (required) |
| `base_url` | `SIGNVAULT_BASE_URL` | `https://api.signvault.com` | Override for staging |
| `timeout` | `SIGNVAULT_TIMEOUT` | `30` | Request timeout in seconds |
| `max_retries` | `SIGNVAULT_MAX_RETRIES` | `1` | Retries on 429 / 5xx |
| `webhook_secret` | `SIGNVAULT_WEBHOOK_SECRET` | — | Webhook signing secret |

---

## Testing

```bash
composer test           # All tests (unit)
composer test:integration  # Integration (requires real SIGNVAULT_API_KEY)
```

In your own test suite, bind a mock before resolution:

```php
$this->app->instance(\SignVault\SignVault::class, $mockClient);
```

Or use `orchestra/testbench` with `SignVaultServiceProvider` and override config in `defineEnvironment`.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT.
