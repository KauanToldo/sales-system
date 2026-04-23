<?php

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => $_ENV['APP_DB_HOST'],
            'name' => $_ENV['APP_DB_NAME'],
            'user' => $_ENV['APP_DB_USER'],
            'pass' => $_ENV['APP_DB_PASSWORD'],
            'port' => $_ENV['APP_DB_PORT'],
            'charset' => 'utf8',
        ],
    ],
];