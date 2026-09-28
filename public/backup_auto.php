<?php
/**
 * Silent automatic-backup tick.
 *
 * Included from the admin sidebar, which every admin page already includes, so
 * scheduled backups work without touching each page and without needing a cron
 * daemon (XAMPP on Windows has none).
 *
 * This file is deliberately framework-free and never produces any output: a
 * backup failure must never break or delay the page the admin is looking at.
 * It runs at most once per browser session, then falls through to the interval
 * check in DatabaseBackup::autoTick().
 */

if (!defined('MSWD_BACKUP_AUTO')) {
    define('MSWD_BACKUP_AUTO', 1);

    function mswd_backup_auto_tick(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        // Cheap per-session guard so we are not re-checking on every request.
        if (!empty($_SESSION['mswd_backup_auto_checked'])) return;
        $_SESSION['mswd_backup_auto_checked'] = 1;

        try {
            $root = dirname(__DIR__);
            require_once $root . '/app/Support/SystemConfig.php';
            require_once $root . '/app/Support/DatabaseBackup.php';
            \App\Support\DatabaseBackup::autoTick();
        } catch (\Throwable $e) {
            // Swallow deliberately - this runs inside every admin page render.
        }
    }

    mswd_backup_auto_tick();
}
