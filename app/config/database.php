<?php
/**
 * Database Configuration
 * MySQL connection settings (for hosts like Hostinger that don't support PostgreSQL)
 *
 * To use this file, either:
 *  - rename it to database.php (replacing the PostgreSQL config), or
 *  - copy its contents into database.php on your MySQL host.
 */

return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'port'     => getenv('DB_PORT')     ?: '3306',
    'dbname'   => getenv('DB_NAME')     ?: 'icalgc_db1118',
    'username' => getenv('DB_USER')     ?: 'root',
    'password' => getenv('DB_PASS')     ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
