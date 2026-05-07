<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Signori API Key
    |--------------------------------------------------------------------------
    |
    | Your Signori bearer token. Keep it in .env as SIGNORI_API_KEY.
    | Never commit the raw key to version control.
    |
    */
    'api_key' => env('SIGNORI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Override to point at a staging or self-hosted Signori instance.
    | Defaults to https://api.signori.ai when not set.
    |
    */
    'base_url' => env('SIGNORI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum seconds to wait for a response. File uploads may need a
    | higher value depending on document size.
    |
    */
    'timeout' => env('SIGNORI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    |
    | How many times to retry on 429 (rate limit) or 5xx errors before
    | surfacing the exception to your application.
    |
    */
    'max_retries' => env('SIGNORI_MAX_RETRIES', 1),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Signing secret used to verify incoming webhook payloads. Set this in
    | .env and pass it to Signori::webhooks()->verify() / the helper.
    |
    */
    'webhook_secret' => env('SIGNORI_WEBHOOK_SECRET'),

];
