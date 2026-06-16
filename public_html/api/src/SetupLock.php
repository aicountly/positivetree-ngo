<?php

declare(strict_types=1);

namespace App;

class SetupLock
{
    public static function path(): string
    {
        return __DIR__ . '/../data/.setup_complete';
    }

    public static function exists(): bool
    {
        return is_readable(self::path());
    }

    public static function markCompleted(): void
    {
        $path = self::path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (!self::exists()) {
            file_put_contents($path, nowIso() . PHP_EOL);
        }
    }
}
