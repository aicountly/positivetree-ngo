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

        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0750, true);
        }

        $dbPath = $dataDir . '/donations.sqlite';
        $isNew = !file_exists($dbPath);

        self::$pdo = new \PDO('sqlite:' . $dbPath);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        if ($isNew) {
            self::migrate();
        }

        return self::$pdo;
    }

    public static function migrate(): void
    {
        $schema = file_get_contents(__DIR__ . '/../schema.sql');
        if ($schema === false) {
            throw new \RuntimeException('Unable to read schema.sql');
        }

        self::connection()->exec($schema);
    }
}
