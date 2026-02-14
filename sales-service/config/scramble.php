<?php

return [
    /*
     * API path — Scramble auto-documents all routes under this prefix.
     */
    'api_path' => 'api',

    /*
     * API domain — leave null to use the app domain.
     */
    'api_domain' => null,

    /*
     * Info block for the OpenAPI spec.
     */
    'info' => [
        'version' => '1.0.0',
        'description' => 'Sales Service API — handles orders and triggers NewOrder workflow',
    ],

    /*
     * Scramble UI + JSON doc paths:
     *   GET /docs/api      → Swagger UI
     *   GET /docs/api.json → OpenAPI JSON
     */
    'ui' => [
        'enabled' => true,
    ],

    'servers' => [],
];
