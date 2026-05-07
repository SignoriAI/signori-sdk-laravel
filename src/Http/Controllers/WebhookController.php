<?php

declare(strict_types=1);

namespace Signori\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Signori\Exceptions\SignoriException;
use Signori\Laravel\Events\WebhookReceived;
use Signori\Resources\Webhooks;

/**
 * Drop-in webhook endpoint controller.
 *
 * Register in your routes file:
 *
 *   Route::post('/webhooks/signori', \Signori\Laravel\Http\Controllers\WebhookController::class);
 *
 * Make sure to exclude this route from CSRF verification in your
 * App\Http\Middleware\VerifyCsrfToken $except array.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Signori-Signature', '');

        // Coerce nulls to '' so an unset/empty SIGNORI_WEBHOOK_SECRET is
        // treated as "no secret configured — skip verification" rather than
        // type-erroring against Webhooks::verify()'s string $secret param.
        $secret = (string) (config('signori.webhook_secret') ?? '');

        // Verify signature when a secret is configured.
        if ($secret !== '' && ! Webhooks::verify($payload, $signature, $secret)) {
            return response('Invalid signature', 403);
        }

        try {
            $event = Webhooks::constructEvent($payload);
        } catch (SignoriException) {
            return response('Invalid payload', 400);
        }

        // Signori puts the event type in the ``X-Signori-Event`` header
        // (the JSON body holds only the event data, not a wrapping
        // ``{"event": ...}`` envelope). Honour the header when present;
        // fall back to whatever ``constructEvent`` extracted from the body
        // so an envelope-style sender keeps working too.
        $eventType = (string) $request->header('X-Signori-Event', '');
        if ($eventType === '') {
            $eventType = $event['event'];
        }

        // Fire a Laravel event so application listeners can react.
        event(new WebhookReceived($eventType, $event['data'], $request));

        return response('OK', 200);
    }
}
