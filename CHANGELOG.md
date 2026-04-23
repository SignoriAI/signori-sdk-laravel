# Changelog

All notable changes to the SignVault Laravel SDK.

## [1.0.0] — 2026-04-23

Initial release.

### Added
- `SignVaultServiceProvider` — auto-registered via Laravel package discovery
- `SignVault` facade alias backed by the bound `\SignVault\SignVault` singleton
- Publishable config `config/signvault.php` (`vendor:publish --tag=signvault-config`)
- `WebhookController` — drop-in controller that verifies HMAC-SHA256 signature and fires `WebhookReceived`
- `WebhookReceived` event — carries `$event`, `$data`, and the original `$request`
- `VerifyWebhookSignature` middleware — signature guard for custom webhook handlers
- PHPUnit test suite using `orchestra/testbench`
- Requires Laravel 10 or 11, PHP 8.1+
- Delegates all API calls to `signvault/signvault-php` ^1.0
