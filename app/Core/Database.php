<?php
/**
 * Database - PDO singleton for PostgreSQL or MySQL
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require APP_PATH . '/config/database.php';
            $driver = $cfg['driver'] ?? 'pgsql';

            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['dbname'],
                    $cfg['charset'] ?? 'utf8mb4'
                );
            } else {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['dbname']
                );
            }

            try {
                self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
                // Set session timezone to Asia/Bangkok
                if ($driver === 'mysql') {
                    self::$instance->exec("SET time_zone = '+07:00'");
                } else {
                    self::$instance->exec("SET timezone = 'Asia/Bangkok'");
                }
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Database connection error. Please try again later.');
            }
        }

        return self::$instance;
    }

    // Prevent direct instantiation
    private function __construct() {}
    private function __clone() {}
}
