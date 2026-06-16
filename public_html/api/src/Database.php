<?php

declare(strict_types=1);

namespace App;

class Database
{
    private static ?\PDO $pdo = null;

    public static function connection(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException('PDO SQLite extension is not enabled on this server');
        }

        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
            throw new \RuntimeException('Unable to create the api/data directory');
        }

        if (!is_writable($dataDir)) {
            throw new \RuntimeException('The api/data directory is not writable by PHP');
        }

        $dbPath = $dataDir . '/donations.sqlite';
        $isNew = !file_exists($dbPath);

        self::$pdo = new \PDO('sqlite:' . $dbPath);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        if ($isNew) {
            self::migrate();
        }

        return self::$pdo;
    }

    public static function migrate(): void
    {
        $schemaPath = __DIR__ . '/../schema.sql';
        if (!is_readable($schemaPath)) {
            throw new \RuntimeException('Unable to read schema.sql');
        }

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new \RuntimeException('Unable to read schema.sql');
        }

        self::$pdo->exec($schema);
    }
}
