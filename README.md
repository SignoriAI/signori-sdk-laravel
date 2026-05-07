# Signori Laravel SDK

Official Laravel integration for the [Signori](https://signori.ai) e-signature API.  
Wraps [`signori/signori-php`](https://github.com/signori/signori-sdk-php) with a service provider, Facade, webhook controller, and signature middleware.

---

## Requirements

| | Minimum |
|---|---|
| PHP | 8.1 |
| Laravel | 10 or 11 |
| signori/signori-php | ^1.0 |

---

## Installation

```bash
composer require signori/laravel-sdk
```

Laravel's package auto-discovery registers the service provider and `Signori` alias automatically.

Publish the config file:

```bash
php artisan vendor:publish --tag=signori-config
```

Add your credentials to `.env`:

```env
SIGNORI_API_KEY=sv_live_your_key_here
SIGNORI_WEBHOOK_SECRET=your_webhook_secret_here
```

---

## Usage

### Via dependency injection

```php
use Signori\Signori;

class ContractService
{
    public function __construct(private readonly Signori $signori) {}

    public function sendForSigning(string $path, string $title, array $signers): string
    {
        $doc = $this->signori->documents->upload($path, $title);
        $this->signori->documents->send($doc->id, $signers);
        return $doc->id;
    }
}
```

### Via Facade

```php
use Signori\Laravel\Facades\Signori;

$client = Signori::getFacadeRoot();   // returns the bound \Signori\Signori instance

$doc = $client->documents->upload(storage_path('app/contract.pdf'), 'NDA');

$client->documents->send($doc->id, [
    ['email' => 'alice@example.com', 'full_name' => 'Alice Smith', 'role' => 'signer'],
]);
```

### Documents

```php
// Upload
$doc = $signori->documents->upload('/path/to/file.pdf', 'Contract', 'contract');

// List with filters
$page = $signori->documents->list(['status' => 'pending', 'limit' => 25]);
foreach ($page->items as $doc) {
    echo "{$doc->id} {$doc->title} {$doc->status}\n";
}

// Get single
$doc = $signori->documents->get('doc_abc123');

// Send for signing
$signori->documents->send($doc->id, [
    ['email' => 'alice@example.com', 'full_name' => 'Alice Smith', 'role' => 'signer'],
    ['email' => 'bob@example.com',   'full_name' => 'Bob Jones',   'role' => 'approver'],
], message: 'Please review and sign.', expiryDays: 14);

// Download signed PDF
$bytes = $signori->documents->downloadSigned($doc->id);

// Void
$signori->documents->void($doc->id, 'Sent to wrong recipient');

// Audit trail
$audit = $signori->documents->auditTrail($doc->id);
```

### Templates

```php
$doc = $signori->templates->createDocument(
    templateId: 'tpl_abc123',
    fieldValues: ['party_name' => 'Acme Corp', 'effective_date' => '2026-01-01'],
    signers: [['email' => 'ceo@acme.com', 'full_name' => 'Jane CEO']],
);
```

### API Keys & Webhooks

```php
// API Keys
$key  = $signori->apiKeys->create('CI deploy key');
$page = $signori->apiKeys->list();
$signori->apiKeys->delete($key->id);

// Webhooks
$hook = $signori->webhooks->create('https://example.com/hooks/sv', ['document.completed']);
$signori->webhooks->update($hook->id, ['is_active' => false]);
$signori->webhooks->delete($hook->id);
```

---

## Webhooks

### Option A — Drop-in controller

Register a route and exclude it from CSRF:

```php
// routes/api.php (already CSRF-exempt) or routes/web.php
Route::post('/webhooks/signori', \Signori\Laravel\Http\Controllers\WebhookController::class);
```

If using `routes/web.php`, add the path to `VerifyCsrfToken::$except`.

The controller:
1. Verifies the HMAC-SHA256 signature against `SIGNORI_WEBHOOK_SECRET` (skips if secret is empty).
2. Fires `Signori\Laravel\Events\WebhookReceived` on success.

Listen for the event:

```php
// EventServiceProvider
use Signori\Laravel\Events\WebhookReceived;

protected $listen = [
    WebhookReceived::class => [
        App\Listeners\HandleSignoriWebhook::class,
    ],
];
```

```php
// App\Listeners\HandleSignoriWebhook
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
Route::post('/webhooks/signori', MyWebhookHandler::class)
    ->middleware(\Signori\Laravel\Http\Middleware\VerifyWebhookSignature::class)
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### Manual verification

```php
use Signori\Resources\Webhooks;

$payload   = $request->getContent();
$signature = $request->header('X-Signori-Signature', '');

if (! Webhooks::verify($payload, $signature, config('signori.webhook_secret'))) {
    abort(403);
}

$event = Webhooks::constructEvent($payload);
// $event['event'] — e.g. "document.completed"
// $event['data']  — decoded payload
```

---

## Error handling

```php
use Signori\Exceptions\AuthException;
use Signori\Exceptions\NotFoundException;
use Signori\Exceptions\ValidationException;
use Signori\Exceptions\RateLimitException;
use Signori\Exceptions\ApiException;

try {
    $doc = $signori->documents->get('doc_xyz');
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
| `api_key` | `SIGNORI_API_KEY` | — | Bearer token (required) |
| `base_url` | `SIGNORI_BASE_URL` | `https://api.signori.ai` | Override for staging |
| `timeout` | `SIGNORI_TIMEOUT` | `30` | Request timeout in seconds |
| `max_retries` | `SIGNORI_MAX_RETRIES` | `1` | Retries on 429 / 5xx |
| `webhook_secret` | `SIGNORI_WEBHOOK_SECRET` | — | Webhook signing secret |

---

## Testing

```bash
composer test           # All tests (unit)
composer test:integration  # Integration (requires real SIGNORI_API_KEY)
```

In your own test suite, bind a mock before resolution:

```php
$this->app->instance(\Signori\Signori::class, $mockClient);
```

Or use `orchestra/testbench` with `SignoriServiceProvider` and override config in `defineEnvironment`.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT.
