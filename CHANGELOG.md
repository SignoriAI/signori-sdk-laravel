# Changelog

All notable changes to the Signori Laravel SDK.

## [1.0.0] — 2026-04-23

Initial release.

### Added
- `SignoriServiceProvider` — auto-registered via Laravel package discovery
- `Signori` facade alias backed by the bound `\Signori\Signori` singleton
- Publishable config `config/signori.php` (`vendor:publish --tag=signori-config`)
- `WebhookController` — drop-in controller that verifies HMAC-SHA256 signature and fires `WebhookReceived`
- `WebhookReceived` event — carries `$event`, `$data`, and the original `$request`
- `VerifyWebhookSignature` middleware — signature guard for custom webhook handlers
- PHPUnit test suite using `orchestra/testbench`
- Requires Laravel 10 or 11, PHP 8.1+
- Delegates all API calls to `signori/signori-php` ^1.0
