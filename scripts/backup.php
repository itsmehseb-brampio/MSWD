<?php
/**
 * Command-line database backup.
 *
 * Intended for Windows Task Scheduler (XAMPP has no cron). Example task action:
 *
 *   Program:  C:\xampp\php\php.exe
 *   Arguments: "C:\xampp\htdocs\MSWD\scripts\backup.php"
 *   Start in:  C:\xampp\htdocs\MSWD
 *   Trigger:   Daily, e.g. 02:00
 *
 * Options:
 *   --keep=N     keep N backups instead of the configured BACKUP_KEEP
 *   --quiet      log only, no console output
 *
 * Exit codes: 0 success, 1 backup failed, 2 configuration problem.
 *
 * Framework-free on purpose - it must run with a bare PHP binary and no web
 * server, without bootstrapping Laravel.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

$root = dirname(__DIR__);

$options = [
    'keep'   => null,
    'quiet'  => false,
];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--quiet' || $arg === '-q') {
        $options['quiet'] = true;
    } elseif (preg_match('/^--keep=(\d+)$/', $arg, $m)) {
        $options['keep'] = (int) $m[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php scripts/backup.php [--keep=N] [--quiet]\n";
        exit(0);
    }
}

function say(bool $quiet, string $line): void
{
    if ($quiet) return;
    echo $line . PHP_EOL;
}

function log_line(string $line): void
{
    $dir = $GLOBALS['root'] . '/storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents(
        $dir . '/mswd-backup.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

$GLOBALS['root'] = $root;

require_once $root . '/app/Support/SystemConfig.php';
require_once $root . '/app/Support/DatabaseBackup.php';

use App\Support\DatabaseBackup;

$settings = DatabaseBackup::settings();
$keep = $options['keep'] !== null ? max(1, (int) $options['keep']) : (int) $settings['keep'];

say($options['quiet'], "[" . date('Y-m-d H:i:s') . '] MSWD database backup starting...');
say($options['quiet'], '  Database : ' . DatabaseBackup::env('DB_DATABASE', '(not set)'));
say($options['quiet'], '  Folder   : ' . DatabaseBackup::dir(false));

$binary = DatabaseBackup::mysqlDumpBinary();
if ($binary === null) {
    say($options['quiet'], "  ERROR: mysqldump was not found.");
    say($options['quiet'], "         Set BACKUP_MYSQLDUMP in .env to its full path.");
    log_line('FAILED - mysqldump not found');
    exit(2);
}

$result = DatabaseBackup::create();

if (empty($result['ok'])) {
    say($options['quiet'], '  ERROR: ' . $result['error']);
    log_line('FAILED - ' . $result['error']);
    exit(1);
}

say($options['quiet'], '  Created  : ' . $result['file'] . ' (' . DatabaseBackup::humanSize((int) $result['size']) . ')');

$removed = DatabaseBackup::prune($keep);
if ($removed) {
    say($options['quiet'], '  Pruned   : ' . count($removed) . ' older backup(s) (keeping ' . $keep . ')');
}

$total = DatabaseBackup::all();
say($options['quiet'], '  Total    : ' . count($total) . ' backup(s) on disk');
say($options['quiet'], "[" . date('Y-m-d H:i:s') . '] Backup complete.');

log_line('OK - ' . $result['file'] . ' (' . DatabaseBackup::humanSize((int) $result['size']) . '), keeping ' . $keep);
exit(0);
