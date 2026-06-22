<?php
/**
 * Database Configuration
 * PostgreSQL connection settings
 */

return [
    'driver'   => 'pgsql',
    'host'     => getenv('DB_HOST')     ?: '127.0.0.1',
    'port'     => getenv('DB_PORT')     ?: '5432',
    'dbname'   => getenv('DB_NAME')     ?: 'icalgc2026',
    'username' => getenv('DB_USER')     ?: 'postgres',
    'password' => getenv('DB_PASS')     ?: 'password',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
