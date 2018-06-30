<?php
return [
    'db' => [
        'nodes' => [
            'admin' => [
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'dbUser',
                'password' => 'password',
                'database_name' => 'dbName',
                'charset' => 'utf-8',
            ],
        ],
        'schema' => [
            'admin' => [
                'db' => 'admin',
                'proxies' => ['admin' => 1],
                'default' => 'admin',
                'shard' => [
                ],
            ],

        ],
    ],
];
