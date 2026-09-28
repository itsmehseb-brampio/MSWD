<?php

namespace App\Support;

/**
 * Framework-free database backup helper.
 *
 * This class deliberately has NO Illuminate dependencies. The automatic backup
 * tick runs from the sidebar include on every admin page load, so it has to be
 * cheap and it has to work even when the Laravel kernel was never bootstrapped.
 *
 * Every setting is read from the project .env (never hard-coded):
 *   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   BACKUP_DIR              where dumps are written (MUST be outside the web root)
 *   BACKUP_MYSQLDUMP        path to the mysqldump binary
 *   BACKUP_KEEP             how many backups to keep before pruning
 *   BACKUP_AUTO_ENABLED     "true"/"false"  default for the auto-backup toggle
 *   BACKUP_AUTO_EVERY_HOURS how often an automatic backup should run
 */
class DatabaseBackup
{
    /* ---------------------------------------------------------------- paths */

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /** Absolute path of the backup directory, created if missing. */
    public static function dir(bool $create = true): string
    {
        $dir = self::env('BACKUP_DIR', 'storage/app/private/backups');
        $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        $dir = self::isAbsolute($dir) ? $dir : self::projectRoot() . DIRECTORY_SEPARATOR . $dir;

        if ($create && !is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function stateFile(): string
    {
        return self::dir() . DIRECTORY_SEPARATOR . '_state.json';
    }

    private static function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#^([A-Za-z]:[\\\\/]|[\\\\/]{1,2})#', $path);
    }

    /* -------------------------------------------------------------- settings */

    /**
     * Runtime settings: .env defaults, overridden by whatever the Admin saved
     * on the Backups page (kept in a small JSON state file so no schema change
     * and no migration is required).
     */
    public static function settings(): array
    {
        $defaults = [
            'auto_enabled'     => self::envBool('BACKUP_AUTO_ENABLED', false),
            'auto_every_hours' => max(1, (int) self::env('BACKUP_AUTO_EVERY_HOURS', '24')),
            'keep'             => max(1, (int) self::env('BACKUP_KEEP', '10')),
        ];

        $saved = self::readState();
        return array_merge($defaults, array_intersect_key($saved, $defaults));
    }

    public static function saveSettings(array $incoming): array
    {
        $current = self::settings();
        $next = $current;

        if (array_key_exists('auto_enabled', $incoming)) {
            $next['auto_enabled'] = (bool) $incoming['auto_enabled'];
        }
        if (isset($incoming['auto_every_hours'])) {
            $next['auto_every_hours'] = max(1, min(8760, (int) $incoming['auto_every_hours']));
        }
        if (isset($incoming['keep'])) {
            $next['keep'] = max(1, min(999, (int) $incoming['keep']));
        }

        self::writeState(['settings' => $next]);

        // A lowered retention should take effect immediately.
        self::prune((int) $next['keep']);

        return $next;
    }

    private static function readState(): array
    {
        $file = self::stateFile();
        if (!is_file($file)) return [];
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function writeState(array $data): bool
    {
        $file = self::stateFile();
        if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $ok = @file_put_contents($file, $json, LOCK_EX) !== false;
        if ($ok) @chmod($file, 0644);
        return $ok;
    }

    private static function lastAutoAt(): ?int
    {
        $state = self::readState();
        return isset($state['last_auto_at']) ? (int) $state['last_auto_at'] : null;
    }

    private static function touchAuto(int $when): void
    {
        $state = self::readState();
        $state['last_auto_at'] = $when;
        self::writeState($state);
    }

    /* ---------------------------------------------------------------- create */

    /**
     * Create a timestamped .sql dump.
     *
     * @return array{ok:bool,file?:string,path?:string,size?:int,error?:string}
     */
    public static function create(): array
    {
        $dir = self::dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['ok' => false, 'error' => 'Backup directory is not writable: ' . $dir];
        }

        $dbName = (string) self::env('DB_DATABASE', '');
        if ($dbName === '') {
            return ['ok' => false, 'error' => 'DB_DATABASE is not set in .env.'];
        }

        $binary = self::mysqlDumpBinary();
        if ($binary === null) {
            return ['ok' => false, 'error' => 'mysqldump was not found. Set BACKUP_MYSQLDUMP in .env to its full path.'];
        }

        $stamp  = date('Y-m-d_His');
        $file   = 'mswd_backup_' . $stamp . '.sql';
        $target = $dir . DIRECTORY_SEPARATOR . $file;

        // Credentials are passed through a private option file so they never
        // appear in the process list (visible to any local user via tasklist).
        $defaultsFile = self::writeDefaultsFile();
        if ($defaultsFile === null) {
            return ['ok' => false, 'error' => 'Could not create a temporary mysqldump options file.'];
        }

        $parts = [
            self::quote($binary),
            '--defaults-extra-file=' . self::quote($defaultsFile),
            '--host=' . self::quote((string) self::env('DB_HOST', '127.0.0.1')),
            '--port=' . self::quote((string) self::env('DB_PORT', '3306')),
            '--user=' . self::quote((string) self::env('DB_USERNAME', 'root')),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--add-drop-table',
            '--skip-lock-tables',
            '--default-character-set=utf8mb4',
            '--result-file=' . self::quote($target),
            self::quote($dbName),
        ];

        $cmd = implode(' ', $parts) . ' 2>&1';
        $output = [];
        $code = 0;
        @exec($cmd, $output, $code);
        @unlink($defaultsFile);

        if ($code !== 0 || !is_file($target) || filesize($target) === 0) {
            @unlink($target);
            $detail = trim(implode(' ', $output));
            return ['ok' => false, 'error' => 'mysqldump failed (exit ' . $code . '). ' . $detail];
        }

        $size = filesize($target);
        self::prune((int) self::settings()['keep']);

        return ['ok' => true, 'file' => $file, 'path' => $target, 'size' => $size];
    }

    /** Locate mysqldump: .env override, then common XAMPP paths, then PATH. */
    public static function mysqlDumpBinary(): ?string
    {
        $configured = (string) self::env('BACKUP_MYSQLDUMP', '');
        if ($configured !== '' && @is_file($configured)) {
            return $configured;
        }

        $exe = DIRECTORY_SEPARATOR === '\\' ? 'mysqldump.exe' : 'mysqldump';
        $candidates = [
            'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $exe,
            'C:' . DIRECTORY_SEPARATOR . 'wamp64' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'mysql8.0.31' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $exe,
            'C:' . DIRECTORY_SEPARATOR . 'laragon' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . $exe,
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $c) {
            if (@is_file($c)) return $c;
        }

        // last resort: ask the shell
        $which = @exec('command -v mysqldump 2>/dev/null', $out, $rc);
        if ($rc === 0 && !empty($out[0])) return trim($out[0]);

        return null;
    }

    private static function writeDefaultsFile(): ?string
    {
        $file = self::dir() . DIRECTORY_SEPARATOR . '_dump_' . getmypid() . '.cnf';
        $password = (string) self::env('DB_PASSWORD', '');

        $body = "[client]\nhost=" . self::env('DB_HOST', '127.0.0.1') . "\n"
              . "port=" . self::env('DB_PORT', '3306') . "\n"
              . "user=" . self::env('DB_USERNAME', 'root') . "\n"
              . 'password="' . str_replace('"', '\"', $password) . "\"\n";

        if (@file_put_contents($file, $body, LOCK_EX) === false) return null;
        @chmod($file, 0600);
        return $file;
    }

    private static function quote(string $v): string
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return "'" . str_replace("'", "'\\''", $v) . "'";
        }
        return '"' . str_replace('"', '""', $v) . '"';
    }

    /* ------------------------------------------------------------------ list */

    /**
     * All available backups, newest first.
     * @return array<int,array{name:string,size:int,mtime:int,path:string}>
     */
    public static function all(): array
    {
        $dir = self::dir(false);
        if (!is_dir($dir)) return [];

        $out = [];
        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (!preg_match('/^mswd_backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql$/', $entry)) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) continue;
            $out[] = [
                'name'  => $entry,
                'size'  => (int) filesize($path),
                'mtime' => (int) filemtime($path),
                'path'  => $path,
            ];
        }

        usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    /**
     * Resolve a requested backup name to a real path inside the backup dir.
     * Returns null unless the name matches the strict pattern AND the file
     * exists AND it resolves to a file still inside the backup directory.
     */
    public static function resolve(string $name): ?string
    {
        $name = basename(trim($name));
        if (!preg_match('/^mswd_backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql$/', $name)) {
            return null;
        }
        $dir = self::dir(false);
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) return null;

        $real = realpath($path);
        $realDir = realpath($dir);
        if ($real === false || $realDir === false) return null;
        if (strpos($real, $realDir . DIRECTORY_SEPARATOR) !== 0) return null;

        return $real;
    }

    public static function delete(string $name): bool
    {
        $path = self::resolve($name);
        return $path !== null ? @unlink($path) : false;
    }

    /** Keep only the newest $keep backups. */
    public static function prune(int $keep): array
    {
        $keep = max(1, $keep);
        $removed = [];
        foreach (array_slice(self::all(), $keep) as $old) {
            if (@unlink($old['path'])) $removed[] = $old['name'];
        }
        return $removed;
    }

    public static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return round($value, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    /* ------------------------------------------------------------- auto tick */

    /**
     * Called from the sidebar on admin page loads. Runs at most one backup per
     * configured interval. Never echoes anything and never throws - a failure
     * here must not take the page down.
     *
     * @return array|null result of the backup, or null when nothing was due
     */
    public static function autoTick(bool $force = false): ?array
    {
        $lock = null;
        try {
            $settings = self::settings();
            if (!$settings['auto_enabled'] && !$force) return null;

            $last = self::lastAutoAt();
            $due = $last === null
                || (time() - $last) >= ((int) $settings['auto_every_hours'] * 3600);

            if (!$due) return null;

            // Guard against several admin sessions firing a dump at the same time.
            $lockPath = self::dir() . DIRECTORY_SEPARATOR . '_auto.lock';
            $lock = @fopen($lockPath, 'c');
            if ($lock === false || !@flock($lock, LOCK_EX | LOCK_NB)) {
                if (is_resource($lock)) fclose($lock);
                return null;
            }

            $result = self::create();
            self::touchAuto(time());

            @flock($lock, LOCK_UN);
            fclose($lock);

            return $result;
        } catch (\Throwable $e) {
            if (is_resource($lock)) {
                @flock($lock, LOCK_UN);
                fclose($lock);
            }
            return null;
        }
    }

    /* -------------------------------------------------------------- .env I/O */

    /** Delegates to SystemConfig so the .env is parsed only once per request. */
    public static function env(string $key, ?string $default = null): ?string
    {
        return SystemConfig::get($key, $default);
    }

    public static function envBool(string $key, bool $default = false): bool
    {
        return SystemConfig::bool($key, $default);
    }

    /* -------------------------------------------------------- role checking */

    /**
     * The session guard on admin pages only proves the account lives in the
     * `admins` table - invited read-only "user" accounts pass it too. Anything
     * sensitive must additionally confirm the account holds the admin role.
     */
    public static function isAdmin(mysqli $conn, int $adminId): bool
    {
        $stmt = $conn->prepare(
            "SELECT r.name
               FROM model_has_roles mr
               JOIN roles r ON r.id = mr.role_id
              WHERE mr.model_type = ? AND mr.model_id = ? AND r.name = 'admin'
              LIMIT 1"
        );
        if (!$stmt) return false;
        $stmt->bind_param('si', 'App\\Models\\Admin', $adminId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }
}
