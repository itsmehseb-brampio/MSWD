<?php

namespace App\Support;

/**
 * Tiny framework-free .env reader.
 *
 * Flat pages in /public cannot assume the Laravel kernel has been bootstrapped,
 * and bootstrapping it on every page load just to read a config value is far
 * too expensive. This reads and caches the .env file once per request.
 *
 * Once the kernel IS booted, Laravel's own env()/config() remain authoritative
 * for the framework; this class exists so helpers can work in both contexts.
 */
class SystemConfig
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::parse(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
        }
        return self::$cache;
    }

    /** Forget the cached values (used by tests and long-running CLI scripts). */
    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $raw = self::get($key);
        if ($raw === null) return $default;
        return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $raw = self::get($key);
        return ($raw === null || !is_numeric($raw)) ? $default : (int) $raw;
    }

    private static function parse(string $file): array
    {
        $out = [];
        if (!is_file($file)) return $out;

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ((array) $lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, 'export ') === 0) $line = substr($line, 7);
            if (strpos($line, '=') === false) continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $len = strlen($value);
            if ($len >= 2
                && (($value[0] === '"' && $value[$len - 1] === '"')
                 || ($value[0] === "'" && $value[$len - 1] === "'"))) {
                $value = substr($value, 1, -1);
            }
            $out[$key] = $value;
        }
        return $out;
    }
}
