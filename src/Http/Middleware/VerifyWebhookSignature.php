<?php

declare(strict_types=1);

namespace Signori\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Signori\Resources\Webhooks;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that rejects requests with an invalid Signori webhook signature.
 *
 * Register on your webhook route instead of using the bundled controller:
 *
 *   Route::post('/webhooks/signori', MyWebhookHandler::class)
 *       ->middleware(\Signori\Laravel\Http\Middleware\VerifyWebhookSignature::class)
 *       ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('signori.webhook_secret', '');

        if ($secret === '') {
            // No secret configured — allow through (useful in local dev).
            return $next($request);
        }

        $payload   = $request->getContent();
        $signature = $request->header('X-Signori-Signature', '');

        if (! Webhooks::verify($payload, $signature, $secret)) {
            abort(403, 'Invalid Signori webhook signature.');
        }

        return $next($request);
    }
}
