<?php
/**
 * Database Backup - Admin only.
 *
 * Admin-only means the account must hold the "admin" role, not merely exist in
 * the admins table: invited read-only "user" accounts are also rows there and
 * pass the ordinary session guard.
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

require_once __DIR__ . '/../app/Support/SystemConfig.php';
require_once __DIR__ . '/../app/Support/DatabaseBackup.php';
require_once __DIR__ . '/../app/Support/SystemMail.php';

use App\Support\DatabaseBackup;
use App\Support\SystemMail;
use App\Support\SystemConfig;

if (!DatabaseBackup::isAdmin($conn, (int) $_SESSION['admin_id'])) {
    http_response_code(403);
    exit('You do not have permission to access database backups.');
}

$message = "";
$msgType = "ok";

/* ------------------------------------------------------------------ actions */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF: a per-session token guards every state-changing form on this page.
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['backup_csrf']) || !hash_equals($_SESSION['backup_csrf'], $postedToken)) {
        $message = "Your session expired. Please try again.";
        $msgType = "err";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $result = DatabaseBackup::create();
            if (!empty($result['ok'])) {
                $message = "Backup created: " . $result['file'] . " (" . DatabaseBackup::humanSize((int) $result['size']) . ").";
                $msgType = "ok";
            } else {
                $message = "Backup failed. " . $result['error'];
                $msgType = "err";
            }
        }

        if ($action === 'delete') {
            $name = $_POST['file'] ?? '';
            if (DatabaseBackup::delete($name)) {
                $message = "Backup " . basename($name) . " deleted.";
                $msgType = "ok";
            } else {
                $message = "That backup could not be deleted.";
                $msgType = "err";
            }
        }

        if ($action === 'save_settings') {
            $saved = DatabaseBackup::saveSettings([
                'auto_enabled'     => isset($_POST['auto_enabled']),
                'auto_every_hours' => $_POST['auto_every_hours'] ?? 24,
                'keep'             => $_POST['keep'] ?? 10,
            ]);
            $message = "Backup settings saved. Automatic backups are now "
                . ($saved['auto_enabled'] ? 'ON' : 'OFF') . ".";
            $msgType = "ok";
        }

        if ($action === 'test_email') {
            $mailStatus = SystemMail::status();
            if (!$mailStatus['ok']) {
                $message = "Email is not configured. " . $mailStatus['reason'];
                $msgType = "err";
            } else {
                $sent = SystemMail::notifyAdmins(
                    'MSWD test email',
                    "This is a test message from the MSWD Data Management System.\n\n"
                    . "If you received it, email notifications are working.",
                    '<p>This is a test message from the <strong>MSWD Data Management System</strong>.</p>'
                    . '<p>If you received it, email notifications are working.</p>',
                    (int) $_SESSION['admin_id']
                );
                if ($sent['sent'] > 0) {
                    $message = "Test email sent to " . $sent['sent'] . " address(es).";
                    $msgType = "ok";
                } else {
                    $message = "No test email could be sent. Check MAIL_USERNAME / MAIL_PASSWORD in .env, and make sure 2-Step Verification is on with an App Password.";
                    $msgType = "err";
                }
            }
        }
    }
}

if (empty($_SESSION['backup_csrf'])) {
    $_SESSION['backup_csrf'] = bin2hex(random_bytes(16));
}

/* -------------------------------------------------------------------- data */

$settings = DatabaseBackup::settings();
$backups = DatabaseBackup::all();
$mailStatus = SystemMail::status();
$mysqlDump = DatabaseBackup::mysqlDumpBinary();
$backupDir = DatabaseBackup::dir(false);
$lastAuto = null;
$stateFile = $backupDir . DIRECTORY_SEPARATOR . '_state.json';
if (is_file($stateFile)) {
    $state = json_decode((string) @file_get_contents($stateFile), true);
    if (is_array($state) && !empty($state['last_auto_at'])) {
        $lastAuto = (int) $state['last_auto_at'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Database Backup - Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Roboto','Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.35rem;}
.admin-profile{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.25);}
.admin-profile img{width:36px;height:36px;border-radius:50%;border:2px solid white;object-fit:cover;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.page-wrap{max-width:1080px;margin:0 auto;padding:28px 30px;}
.card{background:#fff;border-radius:14px;padding:26px;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:20px;}
.card-title{display:flex;align-items:center;gap:8px;font-size:1.1rem;color:#202124;margin-bottom:4px;}
.card-title i{color:#0072C6;}
.card-sub{font-size:.85rem;color:#5f6368;margin-bottom:18px;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:18px;background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-left-color:#c5221f;}
.btn{padding:11px 22px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
.btn-ghost{background:#fff;color:#0072C6;border:1px solid #0072C6;box-shadow:none;}
.btn-ghost:hover{background:#eef5fd;box-shadow:none;transform:none;}
.btn-danger{background:#fff;color:#c5221f;border:1px solid #f3b6b3;box-shadow:none;}
.btn-danger:hover{background:#fce8e6;box-shadow:none;transform:none;}
.btn-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;}

table{width:100%;border-collapse:collapse;font-size:.88rem;}
th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#0072C6;padding:10px;border-bottom:2px solid #f0f0f0;white-space:nowrap;}
td{padding:12px 10px;border-bottom:1px solid #f0f0f0;color:#4a5562;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
.muted{color:#80868b;font-size:.82rem;}
.empty-state{text-align:center;color:#a5afbd;padding:40px 10px;font-weight:600;font-size:.88rem;}
.empty-state i{display:block;font-size:2.2rem;color:#ccd5e0;margin-bottom:12px;}

.status-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;}
.pill-ok{background:#e6f4ea;color:#188038;}
.pill-bad{background:#fce8e6;color:#c5221f;}

.kv{display:grid;grid-template-columns:190px 1fr;gap:10px 16px;font-size:.86rem;}
.kv .k{color:#5f6368;font-weight:600;}
.kv .v{color:#202124;word-break:break-all;font-family:Consolas,monospace;font-size:.82rem;}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group label{display:flex;align-items:center;gap:8px;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group label i{width:18px;color:#0072C6;text-align:center;}
.form-group input[type=number]{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;}
.form-group input[type=number]:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.form-group small{display:block;color:#80868b;font-size:.76rem;margin-top:5px;}
.checkbox-row{display:flex;align-items:center;gap:10px;font-size:.88rem;color:#333;font-weight:600;margin-bottom:6px;}
.checkbox-row input{width:18px;height:18px;accent-color:#0072C6;}
@media (max-width:700px){.form-grid{grid-template-columns:1fr;}.kv{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-database"></i> Database Backup</h1>
            <div style="position:relative;">
                <div class="admin-profile" onclick="toggleDropdown()">
                    <img src="mapa.png" alt="Admin">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="page-wrap">

            <?php if ($message): ?>
                <div class="alert <?php echo $msgType === 'err' ? 'err' : ''; ?>">
                    <i class="fas fa-<?php echo $msgType === 'err' ? 'triangle-exclamation' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- CREATE -->
            <div class="card">
                <div class="card-title"><i class="fas fa-file-arrow-down"></i> Backups</div>
                <p class="card-sub">
                    Create a full SQL dump of the <code>mapayanan_db</code> database. Backups are stored
                    outside the public web folder and can only be downloaded through this page.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['backup_csrf']); ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="btn-row">
                        <button type="submit" class="btn"><i class="fas fa-database"></i> Create Backup Now</button>
                        <span class="muted">
                            <?php if ($lastAuto): ?>
                                Last automatic backup: <?php echo date('M j, Y g:i A', $lastAuto); ?>
                            <?php else: ?>
                                No automatic backup has run yet.
                            <?php endif; ?>
                        </span>
                    </div>
                </form>
            </div>

            <!-- LIST -->
            <div class="card">
                <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Available Backups</div>
                <p class="card-sub">
                    Keeping the newest <strong><?php echo (int) $settings['keep']; ?></strong> backup(s).
                    Older files are removed automatically.
                </p>

                <?php if (!$backups): ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i>No backups yet. Create one above.</div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Date &amp; Time</th>
                                <th>Size</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td style="font-family:Consolas,monospace;font-size:.82rem;"><?php echo htmlspecialchars($b['name']); ?></td>
                                <td><?php echo date('M j, Y g:i A', $b['mtime']); ?></td>
                                <td><?php echo DatabaseBackup::humanSize((int) $b['size']); ?></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a class="btn btn-ghost" style="padding:7px 14px;font-size:.82rem;"
                                       href="admin_backup_download.php?file=<?php echo urlencode($b['name']); ?>">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($b['name'])); ?> permanently?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['backup_csrf']); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="file" value="<?php echo htmlspecialchars($b['name']); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:7px 14px;font-size:.82rem;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- SETTINGS -->
            <div class="card">
                <div class="card-title"><i class="fas fa-gear"></i> Automatic Backups</div>
                <p class="card-sub">
                    When enabled, a backup is created automatically the first time an Admin opens any
                    system page after the interval has passed. This replaces cron, which is not
                    available on XAMPP/Windows.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['backup_csrf']); ?>">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-grid" style="margin-bottom:18px;">
                        <div class="form-group">
                            <label class="checkbox-row">
                                <input type="checkbox" name="auto_enabled" value="1" <?php echo !empty($settings['auto_enabled']) ? 'checked' : ''; ?>>
                                Enable automatic backups
                            </label>
                            <small>Runs on Admin page loads only. The CLI script below works even when nobody is logged in.</small>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Interval (hours)</label>
                            <input type="number" name="auto_every_hours" min="1" max="8760" value="<?php echo (int) $settings['auto_every_hours']; ?>">
                            <small>How often a new automatic backup should be created.</small>
                        </div>
                    </div>
                    <div class="form-group" style="max-width:260px;margin-bottom:20px;">
                        <label><i class="fas fa-layer-group"></i> Backups to keep</label>
                        <input type="number" name="keep" min="1" max="999" value="<?php echo (int) $settings['keep']; ?>">
                        <small>Oldest files are pruned automatically.</small>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-floppy-disk"></i> Save Settings</button>
                </form>
            </div>

            <!-- ENVIRONMENT -->
            <div class="card">
                <div class="card-title"><i class="fas fa-circle-info"></i> System Status</div>
                <p class="card-sub">Read from the <code>.env</code> file. Secrets are never displayed or stored in source control.</p>
                <div class="kv">
                    <span class="k">Backup folder</span>
                    <span class="v"><?php echo htmlspecialchars($backupDir); ?>
                        <?php echo is_dir($backupDir) ? '<span class="status-pill pill-ok" style="margin-left:6px;">writable</span>'
                                                      : '<span class="status-pill pill-bad" style="margin-left:6px;">missing</span>'; ?>
                    </span>

                    <span class="k">mysqldump</span>
                    <span class="v"><?php echo $mysqlDump ? htmlspecialchars($mysqlDump) . ' <span class="status-pill pill-ok" style="margin-left:6px;">found</span>'
                                                          : 'not found <span class="status-pill pill-bad" style="margin-left:6px;">set BACKUP_MYSQLDUMP in .env</span>'; ?></span>

                    <span class="k">Email transport</span>
                    <span class="v"><?php echo htmlspecialchars(strtoupper(SystemConfig::get('GMAIL_TRANSPORT', 'smtp'))); ?>
                        <span class="status-pill <?php echo $mailStatus['ok'] ? 'pill-ok' : 'pill-bad'; ?>" style="margin-left:6px;">
                            <?php echo $mailStatus['ok'] ? 'ready' : 'not configured'; ?>
                        </span>
                    </span>

                    <span class="k">Mail detail</span>
                    <span class="v" style="font-family:inherit;"><?php echo htmlspecialchars($mailStatus['reason']); ?></span>
                </div>

                <div class="btn-row" style="margin-top:20px;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['backup_csrf']); ?>">
                        <input type="hidden" name="action" value="test_email">
                        <button type="submit" class="btn btn-ghost"><i class="fas fa-envelope"></i> Send Test Email</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleDropdown(){document.getElementById('dropdownMenu').classList.toggle('show');}
document.addEventListener('click',function(e){
    if(!e.target.closest('.admin-profile')){document.getElementById('dropdownMenu').classList.remove('show');}
});
function openSidebar(){document.getElementById('sidebar').classList.remove('hide');}
function closeSidebar(){document.getElementById('sidebar').classList.add('hide');}
function toggleMenu(el){el.parentElement.classList.toggle('active');}
</script>
</body>
</html>
