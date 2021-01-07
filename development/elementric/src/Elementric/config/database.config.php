<?php

return 
[
    'driver' => 'mysql',
    'url' => isset($_ENV['DATABASE_URL']) ? $_ENV['DATABASE_URL'] : '',
    'host' => isset($_ENV['MYSQL_HOST']) ? $_ENV['MYSQL_HOST'] : '127.0.0.1',
    'port' => isset($_ENV['MYSQL_PORT']) ? $_ENV['MYSQL_PORT'] : '3306',
    'database' => isset($_ENV['MYSQL_DATABASE']) ? $_ENV['MYSQL_DATABASE'] : '',
    'username' => isset($_ENV['MYSQL_USERNAME']) ? $_ENV['MYSQL_USERNAME'] : 'root',
    'password' => isset($_ENV['MYSQL_PASSWORD']) ? $_ENV['MYSQL_PASSWORD'] : '',
    'unix_socket' => isset($_ENV['DB_SOCKET']) ? $_ENV['DB_SOCKET'] : '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => isset($_ENV['MYSQL_ATTR_SSL_CA']) ? $_ENV['MYSQL_ATTR_SSL_CA'] : [],
    ]) : []
];