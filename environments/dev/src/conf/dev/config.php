<?php

return [
    'locale' => 'zh-cn',
    'logger' => [
        'app' => [
            'name' => 'app',
            'path' => APP_PATH . '/../log/php' . DIRECTORY_SEPARATOR . 'apptt.log',
            'level' => \Monolog\Logger::DEBUG,
            'bubble' => false,
        ],
        'monitor' => [
            'name' => 'monitor',
            'path' => APP_PATH . '/../log/php' . DIRECTORY_SEPARATOR . 'monitor.log',
            'level' => \Monolog\Logger::DEBUG,
            'bubble' => false,
        ],
        'admin' => [
            'name' => 'admin',
            'path' => APP_PATH . '/../log/php' . DIRECTORY_SEPARATOR . 'admin.log',
            'level' => \Monolog\Logger::DEBUG,
            'bubble' => false,
        ],
        'com' => [
            'name' => 'com',
            'path' => APP_PATH . '/../log/php' . DIRECTORY_SEPARATOR . 'com.log',
            'level' => \Monolog\Logger::DEBUG,
            'bubble' => false,
        ],
        'trace' => [
            'name' => 'com',
            'path' => APP_PATH . '/../log/php' . DIRECTORY_SEPARATOR . 'trace.log',
            'level' => \Monolog\Logger::DEBUG,
            'bubble' => false,
        ],
    ],
];
