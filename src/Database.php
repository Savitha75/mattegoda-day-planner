<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;    // the one shared connection

    private function __construct() {}   // blocks "new Database()"

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {      // first call only: connect
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errors throw, never fail silently
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['name' => ...]
                PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
            ]);
        }
        return self::$pdo;              // every later call: same object
    }
}