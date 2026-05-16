<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', ':memory:'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'inventory_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
        'eventbus' => [
            'driver' => 'mysql',
            'host' => env('EVENTBUS_DB_HOST', '127.0.0.1'),
            'port' => env('EVENTBUS_DB_PORT', '3306'),
            'database' => env('EVENTBUS_DB_DATABASE', 'eventbus_db'),
            'username' => env('EVENTBUS_DB_USERNAME', 'root'),
            'password' => env('EVENTBUS_DB_PASSWORD', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_each_run' => true,
    ],
];
