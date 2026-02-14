<?php

return [
    // Use the local database connection for workflow state storage
    'connection' => env('DB_CONNECTION', 'mysql'),

    // Queue connection for workflow processing
    'queue' => env('QUEUE_CONNECTION', 'database'),
];
