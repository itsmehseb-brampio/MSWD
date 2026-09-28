<?php
/**
 * Streams a database backup to an authenticated Admin.
 *
 * Backups are written to storage/app/private/backups, which is OUTSIDE the
 * public/ web root precisely so they can never be fetched by guessing a URL.
 * This is the only route that may hand a dump to a browser, and it re-checks
 * the admin role on every request.
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

require_once __DIR__ . '/../app/Support/SystemConfig.php';
require_once __DIR__ . '/../app/Support/DatabaseBackup.php';

use App\Support\DatabaseBackup;

/* The session guard is not enough: read-only "user" accounts share it. */
if (!DatabaseBackup::isAdmin($conn, (int) $_SESSION['admin_id'])) {
    http_response_code(403);
    exit('You do not have permission to download database backups.');
}

$name = $_GET['file'] ?? '';
$path = DatabaseBackup::resolve($name);

if ($path === null) {
    http_response_code(404);
    exit('Backup not found.');
}

$size = filesize($path);

// Neutralise any user-agent sniffing and stop the browser rendering the dump.
header('X-Content-Type-Options: nosniff');
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . $size);
header("Content-Disposition: attachment; filename=\"" . basename($path) . "\"");
header('Content-Description: File Transfer');
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');
header('Accept-Ranges: bytes');

if (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($path);
exit;
