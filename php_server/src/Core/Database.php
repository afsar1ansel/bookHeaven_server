<?php

namespace Core;

use PDO;
use Exception;

class Database {
    private static ?PDO $pdo = null;

    /**
     * Get the PDO database connection singleton.
     * Replaces SQLAlchemy initialization.
     */
    public static function get(): PDO {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_NAME'] ?? 'bookheaven';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            
            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (Exception $e) {
                die("Database Connection Error: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
