# Changelog

All notable changes to the SignVault Laravel SDK.

## [1.0.0] — 2026-04-01

Initial release.

### Added
- `SignVaultServiceProvider` — automatic registration via Laravel package discovery
- `SignVault` facade — `SignVault::documents()->upload(...)` anywhere in your app
- Config file `signvault.php` — publishable with `php artisan vendor:publish`
- `DocumentsResource` — upload, list, get, send, void, downloadOriginal, downloadSigned, auditTrail
- `SignersResource` — add, get, remind, remove
- `TemplatesResource` — list, get, createDocument, prefillFromContact
- `WebhooksResource` — create, list, update, delete, test, verifySignature, constructEvent
- `ApiKeysResource` — create, list, delete
- `SignVaultWebhookController` — drop-in controller for receiving and verifying webhooks
- Typed exception hierarchy — `AuthException`, `NotFoundException`, `ValidationException`, `RateLimitException`, `ApiException`
- Auto-retry on 429 (respects `Retry-After`) and 5xx errors (configurable in config)
- Requires Laravel 10+ and PHP 8.1+
- Uses Laravel's built-in `Http` facade for HTTP — respects your existing Guzzle setup
- PHPUnit integration tests using `Http::fake()`; no network required
- Integration tests with auto-skip when `SIGNVAULT_API_KEY` is not set
- Examples: controller, job, event listener, artisan command
