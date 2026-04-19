# SignVault Laravel SDK

Official Laravel package for the [SignVault](https://signvault.com) e-signature API.

**Requirements:** Laravel 10+ · PHP 8.1+  
**Dependencies:** Uses Laravel's built-in `Http` facade — no extra Guzzle config needed

---

## Installation

```bash
composer require signvault/laravel-sdk
```

The service provider is auto-discovered via Laravel's package discovery — no manual registration needed.

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=signvault-config
```

---

## Configuration

Add to your `.env`:

```bash
SIGNVAULT_API_KEY=sv_live_your_api_key
SIGNVAULT_BASE_URL=https://api.signvault.com   # optional
SIGNVAULT_WEBHOOK_SECRET=whsec_...             # optional, for webhook verification
```

Config file `config/signvault.php` (after publishing):

```php
return [
    'api_key'        => env('SIGNVAULT_API_KEY'),
    'base_url'       => env('SIGNVAULT_BASE_URL', 'https://api.signvault.com'),
    'timeout'        => 30,
    'max_retries'    => 1,
    'webhook_secret' => env('SIGNVAULT_WEBHOOK_SECRET'),
];
```

---

## Quick start

### Via facade

```php
use SignVault\Laravel\Facades\SignVault;

// Upload a PDF
$doc = SignVault::documents()->upload(storage_path('app/contract.pdf'), 'Master Services Agreement');

// Add signers and send
SignVault::documents()->send($doc->id, [
    ['email' => 'alice@example.com', 'full_name' => 'Alice Smith'],
]);
```

### Via dependency injection

```php
use SignVault\Laravel\SignVaultClient;

class ContractController extends Controller
{
    public function __construct(private SignVaultClient $sv) {}

    public function send(Request $request): JsonResponse
    {
        $doc = $this->sv->documents()->upload(
            $request->file('pdf')->getRealPath(),
            $request->input('title'),
        );

        return response()->json(['document_id' => $doc->id]);
    }
}
```

---

## Documents

### Upload

```php
// From a file path
$doc = SignVault::documents()->upload('/path/to/file.pdf', 'NDA');

// From an UploadedFile (form upload)
$doc = SignVault::documents()->upload(
    $request->file('pdf')->getRealPath(),
    $request->input('title'),
    'contract',
);
```

### List

```php
$page = SignVault::documents()->list([
    'status'        => 'completed',
    'document_type' => 'contract',
    'search'        => 'Acme',
    'limit'         => 20,
]);

foreach ($page->items as $doc) {
    echo "{$doc->id}  {$doc->title}  {$doc->status}\n";
}

if ($page->hasMore()) {
    $next = SignVault::documents()->list(['cursor' => $page->nextCursor]);
}
```

### Send for signing

```php
$doc = SignVault::documents()->send($doc->id, [
    'signers' => [
        [
            'email'         => 'alice@example.com',
            'full_name'     => 'Alice Smith',
            'role'          => 'signer',
            'auth_method'   => 'email',
            'signing_order' => 1,
        ],
    ],
    'message'        => 'Please review and sign.',
    'expiry_days'    => 14,
    'send_reminders' => true,
]);
```

### Void

```php
SignVault::documents()->void($doc->id, 'Sent to wrong recipient');
```

### Download

```php
$bytes = SignVault::documents()->downloadSigned($doc->id);
return response($bytes, 200, [
    'Content-Type'        => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="signed.pdf"',
]);
```

---

## Webhooks

### Drop-in controller

Register the provided controller in `routes/web.php`:

```php
use SignVault\Laravel\Http\Controllers\SignVaultWebhookController;

Route::post('/webhooks/signvault', SignVaultWebhookController::class);
```

Then listen for events in your `EventServiceProvider`:

```php
use SignVault\Laravel\Events\DocumentCompleted;
use SignVault\Laravel\Events\DocumentDeclined;
use App\Listeners\HandleSignedContract;

protected $listen = [
    DocumentCompleted::class => [HandleSignedContract::class],
    DocumentDeclined::class  => [/* ... */],
];
```

Available events: `DocumentCompleted`, `DocumentDeclined`, `DocumentVoided`, `SignerSigned`, `SignerDeclined`.

### Manual verification

```php
use SignVault\Laravel\Facades\SignVault;

$payload   = $request->getContent();
$signature = $request->header('X-SignVault-Signature');

if (! SignVault::webhooks()->verifySignature($payload, $signature)) {
    abort(403);
}

$event = SignVault::webhooks()->constructEvent($payload);
```

---

## Queued signing jobs

Use a Laravel Job to avoid blocking web requests:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use SignVault\Laravel\Facades\SignVault;

class SendDocumentForSigning implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $filePath,
        private string $title,
        private array  $signers,
    ) {}

    public function handle(): void
    {
        $doc = SignVault::documents()->upload($this->filePath, $this->title);
        SignVault::documents()->send($doc->id, ['signers' => $this->signers]);
    }
}
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
    $doc = SignVault::documents()->get('doc_xyz');
} catch (NotFoundException $e) {
    return response()->json(['error' => 'Not found', 'request_id' => $e->requestId], 404);
} catch (AuthException) {
    return response()->json(['error' => 'Invalid API key'], 401);
} catch (ValidationException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
} catch (RateLimitException) {
    return response()->json(['error' => 'Rate limited'], 429);
} catch (ApiException $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}
```

---

## Testing

The SDK exposes a `SignVault::fake()` helper that uses `Http::fake()` under the hood:

```php
use SignVault\Laravel\Facades\SignVault;

public function test_sends_document_for_signing(): void
{
    SignVault::fake([
        'documents.upload' => ['id' => 'doc_test', 'title' => 'NDA', 'status' => 'draft'],
        'documents.send'   => ['id' => 'doc_test', 'status' => 'pending'],
    ]);

    $this->post('/contracts', ['title' => 'NDA', 'pdf' => UploadedFile::fake()->create('contract.pdf')]);

    SignVault::assertDocumentSent('doc_test');
}
```

---

## Artisan commands

```bash
# List recent documents
php artisan signvault:documents:list --status=pending

# Sync webhook endpoints
php artisan signvault:webhooks:sync
```

---

## Running tests

```bash
composer install
composer test          # Unit tests (Http::fake, no real API calls)
SIGNVAULT_API_KEY=sv_live_... composer test:integration
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT. See [LICENSE](LICENSE).
