<?php
/**
 * Database - PDO singleton for PostgreSQL
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

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname']
            );

            try {
                self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
                // Set PostgreSQL timezone to Asia/Bangkok
                self::$instance->exec("SET timezone = 'Asia/Bangkok'");
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
