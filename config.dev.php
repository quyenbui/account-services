<?php

return call_user_func(function () {
    return [
        'debug' => true,
        'db.options' => [
            'driver' => 'pdo_mysql',
            'dbname' => 'account_services',
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'root',
            'port' => '3306',
            'charset' => 'utf8'
        ]
    ];
});
