<?php

declare(strict_types=1);

namespace SignVault\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * Fired by WebhookController for every verified incoming webhook.
 *
 * Listen to specific event types:
 *
 *   Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
 *       if ($event->event === 'document.completed') {
 *           // $event->data contains the document payload
 *       }
 *   });
 */
class WebhookReceived
{
    use Dispatchable;

    public function __construct(
        /** The SignVault event type, e.g. "document.completed". */
        public readonly string $event,

        /** The decoded event payload. */
        public readonly array $data,

        /** The original HTTP request, in case you need raw headers. */
        public readonly Request $request,
    ) {}
}
