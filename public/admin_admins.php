<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';
$msgType = 'err';

$M_ADMIN = 'App\\Models\\Admin';
$M_BARANGAY = 'App\\Models\\Barangay';

$PERMISSIONS = [
    'view_dashboard' => 'View Dashboard',
    'manage_admin_accounts' => 'Manage Admin Accounts',
    'manage_barangay_accounts' => 'Manage Barangay Accounts',
    'manage_municipal_info' => 'Manage Municipal Info',
    'view_barangay_info' => 'View Barangay Info',
    'edit_barangay_info' => 'Edit Barangay Info',
    'manage_disaster_format' => 'Manage Disaster Format',
    'review_disaster_reports' => 'Review Disaster Reports',
    'submit_disaster_report' => 'Submit Disaster Report',
    'edit_disaster_report' => 'Edit Disaster Report',
    'view_hazard_map' => 'View Hazard Map',
    'edit_hazard_map' => 'Edit Hazard Map',
    'manage_messages' => 'Manage Messages',
    'manage_announcements' => 'Manage Announcements',
    'view_announcements' => 'View Announcements',
    'manage_relief' => 'Manage Relief',
    'confirm_relief' => 'Confirm Relief',
    'view_municipal_contacts' => 'View Municipal Contacts',
];

$GROUPS = [
    'Dashboard' => ['view_dashboard'],
    'Account Management' => ['manage_admin_accounts', 'manage_barangay_accounts'],
    'Barangay Info' => ['view_barangay_info', 'edit_barangay_info'],
    'Municipal Info' => ['manage_municipal_info', 'view_municipal_contacts'],
    'Disaster Report' => ['manage_disaster_format', 'review_disaster_reports', 'submit_disaster_report', 'edit_disaster_report'],
    'Hazard Map' => ['view_hazard_map', 'edit_hazard_map'],
    'Messages' => ['manage_messages'],
    'Announcements' => ['manage_announcements', 'view_announcements'],
    'Relief' => ['manage_relief', 'confirm_relief'],
];

$REQUIRED = [
    'admin' => ['view_dashboard', 'manage_admin_accounts', 'manage_barangay_accounts', 'manage_municipal_info', 'view_barangay_info', 'manage_disaster_format', 'review_disaster_reports', 'edit_disaster_report', 'view_hazard_map', 'manage_messages', 'manage_announcements', 'manage_relief'],
    'barangay' => ['view_dashboard', 'edit_barangay_info', 'submit_disaster_report', 'edit_disaster_report', 'view_hazard_map', 'edit_hazard_map', 'manage_messages', 'view_announcements', 'confirm_relief'],
    'user' => ['view_dashboard'],
];

// Standard role permission sets (used as default checks when creating)
$DEFAULTS = [
    'admin' => array_merge($REQUIRED['admin'], ['view_municipal_contacts']),
    'barangay' => array_merge($REQUIRED['barangay'], ['view_barangay_info', 'view_municipal_contacts']),
    'user' => ['view_dashboard', 'view_barangay_info', 'view_hazard_map', 'view_announcements', 'view_municipal_contacts'],
];

$OFFICIAL_BARANGAYS = [
    'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Barangay V',
    'Binitayan', 'Calbayog', 'Canaway', 'Salvacion',
    'San Antonio Santicon', 'San Antonio Sulong', 'San Francisco',
    'San Isidro Ilawod', 'San Isidro Iraya', 'San Jose',
    'San Roque', 'Santa Cruz', 'Santa Teresa',
];

/* ---------- helpers ---------- */
function role_id_for($name, $guard) {
    global $conn;
    $st = $conn->prepare("SELECT id FROM roles WHERE name = ? AND guard_name = ?");
    if (!$st) return null;
    $st->bind_param('ss', $name, $guard);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows === 0) return null;
    $row = $res->fetch_assoc();
    return (int) $row['id'];
}

function perm_ids_for_guard($guard) {
    global $conn;
    $out = [];
    $st = $conn->prepare("SELECT id, name FROM permissions WHERE guard_name = ?");
    if (!$st) return $out;
    $st->bind_param('s', $guard);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $out[$row['name']] = (int) $row['id'];
    }
    return $out;
}

function account_role_name($modelType, $id) {
    global $conn;
    $st = $conn->prepare(
        "SELECT r.name FROM model_has_roles mr JOIN roles r ON r.id = mr.role_id WHERE mr.model_type = ? AND mr.model_id = ? ORDER BY r.id DESC LIMIT 1"
    );
    if (!$st) return 'none';
    $st->bind_param('si', $modelType, $id);
    $st->execute();
    $res = $st->get_result();
    return $res->num_rows > 0 ? $res->fetch_assoc()['name'] : 'none';
}

function account_perm_names($modelType, $id) {
    global $conn;
    $out = [];
    $st = $conn->prepare(
        "SELECT p.name FROM model_has_permissions mp JOIN permissions p ON p.id = mp.permission_id WHERE mp.model_type = ? AND mp.model_id = ?"
    );
    if (!$st) return $out;
    $st->bind_param('si', $modelType, $id);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $out[] = $row['name'];
    return $out;
}

function sync_role($modelType, $id, $roleId) {
    global $conn;
    $st = $conn->prepare("DELETE FROM model_has_roles WHERE model_type = ? AND model_id = ?");
    $st->bind_param('si', $modelType, $id);
    $st->execute();
    $st = $conn->prepare("INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (?, ?, ?)");
    $st->bind_param('isi', $roleId, $modelType, $id);
    $st->execute();
}

function sync_permissions($modelType, $id, $permIds) {
    global $conn;
    $st = $conn->prepare("DELETE FROM model_has_permissions WHERE model_type = ? AND model_id = ?");
    $st->bind_param('si', $modelType, $id);
    $st->execute();
    $st = $conn->prepare("INSERT INTO model_has_permissions (permission_id, model_type, model_id) VALUES (?, ?, ?)");
    foreach ($permIds as $pid) {
        $st->bind_param('isi', $pid, $modelType, $id);
        $st->execute();
    }
}

function checked_perms_for_guard($guard) {
    global $conn;
    $map = perm_ids_for_guard($guard);
    $ids = [];
    if (!empty($_POST['permissions']) && is_array($_POST['permissions'])) {
        foreach (array_keys($_POST['permissions']) as $name) {
            if (isset($map[$name])) $ids[] = $map[$name];
        }
    }
    return $ids;
}

/* merge required + checked for a role+guard job: returns perm ids */
function merged_perm_ids($role, $guard, $checkedIds = null) {
    global $conn;
    $map = perm_ids_for_guard($guard);
    if ($checkedIds === null) $checkedIds = checked_perms_for_guard($guard);
    $req = isset($GLOBALS['REQUIRED'][$role]) ? $GLOBALS['REQUIRED'][$role] : [];
    $ids = [];
    foreach ($req as $name) {
        if (isset($map[$name])) $ids[$map[$name]] = true;
    }
    foreach ($checkedIds as $pid) $ids[$pid] = true;
    return array_keys($ids);
}

function invite_url($token) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $host . '/invite/' . rawurlencode($token);
}

/* Send the invitation email via the Laravel mail layer. Returns true when mailed. */
function invite_send($id) {
    try {
        if (!class_exists(\App\Models\Admin::class, true)) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        if (!defined('LARAVEL_START')) {
            $la = require __DIR__ . '/../bootstrap/app.php';
            $la->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        }
        $account = \App\Models\Admin::find($id);
        if (!$account) return false;
        return \App\Support\AccountInvitation::send($account);
    } catch (\Throwable $e) {
        return false;
    }
}

/* ---------- POST handling ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = $_POST['account_type'] ?? '';
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (!in_array($type, ['admin', 'barangay', 'user'], true)) {
            $message = 'Invalid account type.';
        } elseif ($type !== 'user' && $password === '') {
            $message = 'Password is required.';
        } elseif ($type !== 'user' && strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } elseif ($type !== 'user' && $password !== $confirm) {
            $message = 'Passwords do not match.';
        } elseif ($type === 'admin' || $type === 'user') {
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            if ($name === '') {
                $message = 'Full name is required.';
            } elseif ($type === 'admin') {
                if ($username === '') {
                    $message = 'Username is required.';
                } else {
                    $st = $conn->prepare("SELECT id FROM admins WHERE username = ?");
                    $st->bind_param('s', $username);
                    $st->execute();
                    if ($st->get_result()->num_rows > 0) {
                        $message = 'Username already exists!';
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $st = $conn->prepare("INSERT INTO admins (name, username, password) VALUES (?, ?, ?)");
                        $st->bind_param('sss', $name, $username, $hashed);
                        if ($st->execute()) {
                            $adminId = $conn->insert_id;
                            sync_role($M_ADMIN, $adminId, role_id_for('admin', 'admin'));
                            sync_permissions($M_ADMIN, $adminId, merged_perm_ids('admin', 'admin'));
                            $message = 'Admin account created successfully!';
                            $msgType = 'ok';
                        } else {
                            $message = 'Error: ' . $conn->error;
                        }
                    }
                }
            } else {
                $email = trim($_POST['email'] ?? '');
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Enter a valid email address.';
                } else {
                    $st = $conn->prepare("SELECT id FROM admins WHERE email = ?");
                    $st->bind_param('s', $email);
                    $st->execute();
                    if ($st->get_result()->num_rows > 0) {
                        $message = 'An account with that email already exists!';
                    } else {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7);
                        $hashed = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                        $st = $conn->prepare("INSERT INTO admins (name, username, password, email, invite_token, invite_expires_at) VALUES (?, NULL, ?, ?, ?, ?)");
                        $st->bind_param('sssss', $name, $hashed, $email, $token, $expires);
                        if ($st->execute()) {
                            $adminId = $conn->insert_id;
                            sync_role($M_ADMIN, $adminId, role_id_for('user', 'admin'));
                            sync_permissions($M_ADMIN, $adminId, merged_perm_ids('user', 'admin'));
                            $link = invite_url($token);
                            $sent = invite_send($adminId);
                            if ($sent) {
                                $message = 'User invited! A sign-up link was emailed to ' . $email . '.';
                                $msgType = 'ok';
                            } else {
                                $message = 'User account created. Invitation email could not be sent (SMTP not configured yet). Invite link: ' . $link;
                                $msgType = 'ok';
                            }
                        } else {
                            $message = 'Error: ' . $conn->error;
                        }
                    }
                }
            }
        } else {
            $bname = trim($_POST['barangay_name'] ?? '');
            $busername = trim($_POST['barangay_username'] ?? '');
            if (!in_array($bname, $OFFICIAL_BARANGAYS, true)) {
                $message = 'Barangay must be one of the 18 official barangays of Malilipot.';
            } else {
                $st = $conn->prepare("SELECT barangay_id FROM barangays WHERE barangay_name = ?");
                $st->bind_param('s', $bname);
                $st->execute();
                if ($st->get_result()->num_rows > 0) {
                    $message = 'This barangay account already exists!';
                } else {
                    $generated = str_replace(' ', '_', $bname);
                    $st = $conn->prepare("SELECT barangay_id FROM barangays WHERE username = ?");
                    $st->bind_param('s', $generated);
                    $st->execute();
                    if ($st->get_result()->num_rows > 0) {
                        $message = 'Barangay username "' . htmlspecialchars($generated) . '" already exists!';
                    } else {
                        $address = trim($_POST['address'] ?? '');
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $st = $conn->prepare("INSERT INTO barangays (barangay_name, username, address, password, disaster_open) VALUES (?, ?, ?, ?, 0)");
                        $st->bind_param('ssss', $bname, $generated, $address, $hashed);
                        if ($st->execute()) {
                            $bId = $conn->insert_id;
                            sync_role($M_BARANGAY, $bId, role_id_for('barangay', 'barangay'));
                            sync_permissions($M_BARANGAY, $bId, merged_perm_ids('barangay', 'barangay'));
                            $message = 'Barangay account "' . htmlspecialchars($generated) . '" created successfully!';
                            $msgType = 'ok';
                        } else {
                            $message = 'Error: ' . $conn->error;
                        }
                    }
                }
            }
        }
    } elseif ($action === 'edit') {
        $type = $_POST['account_type'] ?? '';
        $id = intval($_POST['account_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (!in_array($type, ['admin', 'barangay', 'user'], true) || $id <= 0) {
            $message = 'Invalid account.';
        } elseif ($password !== '' && $password !== $confirm) {
            $message = 'Passwords do not match.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } elseif ($type === 'admin' || $type === 'user') {
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            if ($name === '' || !in_array($role, ['admin', 'user'], true)) {
                $message = 'Name and role are required.';
            } elseif ($type === 'user' && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $message = 'Enter a valid email address for this user.';
            } else {
                $isPending = false;
                if ($username !== '') {
                    $st = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id <> ?");
                    $st->bind_param('si', $username, $id);
                    $st->execute();
                    if ($st->get_result()->num_rows > 0) {
                        $message = 'Username is already used by another account.';
                    }
                } else {
                    $st = $conn->prepare("SELECT id, invite_token FROM admins WHERE id = ?");
                    $st->bind_param('i', $id);
                    $st->execute();
                    $accRow = $st->get_result()->fetch_assoc();
                    if ($accRow && $accRow['invite_token'] !== null) {
                        $isPending = true;
                    } else {
                        $message = 'Username is required for this account.';
                    }
                }
                if ($message === '') {
                    if ($isPending) {
                        $st = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
                        $st->bind_param('ssi', $name, $email, $id);
                    } elseif ($password !== '') {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $st = $conn->prepare("UPDATE admins SET name = ?, username = ?, password = ?, email = ? WHERE id = ?");
                        $st->bind_param('ssssi', $name, $username, $hashed, $email, $id);
                    } else {
                        $st = $conn->prepare("UPDATE admins SET name = ?, username = ?, email = ? WHERE id = ?");
                        $st->bind_param('sssi', $name, $username, $email, $id);
                    }
                    if ($st->execute()) {
                        sync_role($M_ADMIN, $id, role_id_for($role, 'admin'));
                        sync_permissions($M_ADMIN, $id, merged_perm_ids($role, 'admin'));
                        if ($id === intval($_SESSION['admin_id'])) $_SESSION['admin_username'] = $username;
                        $message = ucfirst($type) . ' account updated successfully!';
                        $msgType = 'ok';
                    } else {
                        $message = 'Error: ' . $conn->error;
                    }
                }
            }
        } else {
            $bname = trim($_POST['barangay_name'] ?? '');
            $role = $_POST['role'] ?? '';
            if (!in_array($bname, $OFFICIAL_BARANGAYS, true)) {
                $message = 'Barangay must be one of the 18 official barangays of Malilipot.';
            } elseif (!in_array($role, ['barangay', 'user'], true)) {
                $message = 'Role selection is invalid.';
            } else {
                $st = $conn->prepare("SELECT barangay_id FROM barangays WHERE barangay_name = ? AND barangay_id <> ?");
                $st->bind_param('si', $bname, $id);
                $st->execute();
                if ($st->get_result()->num_rows > 0) {
                    $message = 'Another barangay account uses that name.';
                } else {
                    $generated = str_replace(' ', '_', $bname);
                    $st = $conn->prepare("SELECT barangay_id FROM barangays WHERE username = ? AND barangay_id <> ?");
                    $st->bind_param('si', $generated, $id);
                    $st->execute();
                    if ($st->get_result()->num_rows > 0) {
                        $message = 'Barangay username "' . htmlspecialchars($generated) . '" is already taken.';
                    } else {
                        $hashed = '';
                        $st = null;
                        if ($password !== '') {
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $st = $conn->prepare("UPDATE barangays SET barangay_name = ?, username = ?, address = ?, password = ? WHERE barangay_id = ?");
                            $address = trim($_POST['address'] ?? '');
                            $st->bind_param('ssssi', $bname, $generated, $address, $hashed, $id);
                        } else {
                            $st = $conn->prepare("UPDATE barangays SET barangay_name = ?, username = ?, address = ? WHERE barangay_id = ?");
                            $address = trim($_POST['address'] ?? '');
                            $st->bind_param('sssi', $bname, $generated, $address, $id);
                        }
                        if ($st->execute()) {
                            sync_role($M_BARANGAY, $id, role_id_for($role, 'barangay'));
                            sync_permissions($M_BARANGAY, $id, merged_perm_ids($role, 'barangay'));
                            $message = 'Barangay account updated successfully!';
                            $msgType = 'ok';
                        } else {
                            $message = 'Error: ' . $conn->error;
                        }
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $type = $_POST['account_type'] ?? '';
        $id = intval($_POST['account_id'] ?? 0);
        if ($type === 'admin' || $type === 'user') {
            if ($id === intval($_SESSION['admin_id'])) {
                $message = 'You cannot delete your own account!';
            } else {
                $st = $conn->prepare("DELETE FROM model_has_roles WHERE model_type = ? AND model_id = ?");
                $st->bind_param('si', $M_ADMIN, $id);
                $st->execute();
                $st = $conn->prepare("DELETE FROM model_has_permissions WHERE model_type = ? AND model_id = ?");
                $st->bind_param('si', $M_ADMIN, $id);
                $st->execute();
                $st = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $st->bind_param('i', $id);
                $st->execute();
                $message = 'Account deleted successfully!';
                $msgType = 'ok';
            }
        } elseif ($type === 'barangay') {
            $st = $conn->prepare("DELETE FROM model_has_roles WHERE model_type = ? AND model_id = ?");
            $st->bind_param('si', $M_BARANGAY, $id);
            $st->execute();
            $st = $conn->prepare("DELETE FROM model_has_permissions WHERE model_type = ? AND model_id = ?");
            $st->bind_param('si', $M_BARANGAY, $id);
            $st->execute();
            $st = $conn->prepare("DELETE FROM barangays WHERE barangay_id = ?");
            $st->bind_param('i', $id);
            $st->execute();
            $message = 'Barangay account deleted successfully!';
            $msgType = 'ok';
        }
    } elseif ($action === 'resend_invite') {
        $id = intval($_POST['account_id'] ?? 0);
        if ($id <= 0) {
            $message = 'Invalid account.';
        } else {
            $st = $conn->prepare("SELECT id, name, email FROM admins WHERE id = ?");
            $st->bind_param('i', $id);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            if (!$row) {
                $message = 'Account not found.';
            } elseif ($row['email'] === null || $row['email'] === '') {
                $message = 'This account has no email address yet.';
            } else {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7);
                $st = $conn->prepare("UPDATE admins SET invite_token = ?, invite_expires_at = ? WHERE id = ?");
                $st->bind_param('ssi', $token, $expires, $id);
                $st->execute();
                $sent = invite_send($id);
                if ($sent) {
                    $message = 'Invitation email sent to ' . $row['email'] . '.';
                    $msgType = 'ok';
                } else {
                    $message = 'Invitation link regenerated. Email could not be sent (SMTP not configured yet). Link: ' . invite_url($token);
                    $msgType = 'ok';
                }
            }
        }
    } elseif ($action === 'toggle_disaster') {
        $id = intval($_POST['barangay_id'] ?? 0);
        $st = $conn->prepare("SELECT disaster_open FROM barangays WHERE barangay_id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        $res = $st->get_result();
        if ($res->num_rows > 0) {
            $cur = (int) $res->fetch_assoc()['disaster_open'];
            $new = $cur ? 0 : 1;
            $st = $conn->prepare("UPDATE barangays SET disaster_open = ? WHERE barangay_id = ?");
            $st->bind_param('ii', $new, $id);
            $st->execute();
            $message = 'Disaster operation status updated.';
            $msgType = 'ok';
        }
    }
}

/* ---------- data ---------- */
$admins = $conn->query("SELECT id, name, username, email, invite_token, invite_expires_at FROM admins ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$barangays = $conn->query("SELECT barangay_id, barangay_name, username, address, disaster_open FROM barangays ORDER BY barangay_name")->fetch_all(MYSQLI_ASSOC);

$rows = [];
foreach ($admins as $a) {
    $pending = ($a['username'] === null
        && $a['invite_token'] !== null
        && $a['invite_expires_at'] !== null
        && strtotime($a['invite_expires_at']) > time());
    $rows[] = [
        'type' => 'admin',
        'id' => (int) $a['id'],
        'name' => $a['name'] ?: $a['username'],
        'username' => $a['username'],
        'email' => $a['email'],
        'invite_pending' => $pending,
        'role' => account_role_name($M_ADMIN, (int) $a['id']),
        'barangay' => false,
        'status' => 'Active',
        'disaster_open' => false,
        'address' => '',
        'perms' => account_perm_names($M_ADMIN, (int) $a['id']),
        'is_me' => (int) $a['id'] === intval($_SESSION['admin_id']),
    ];
}
foreach ($barangays as $b) {
    $rows[] = [
        'type' => 'barangay',
        'id' => (int) $b['barangay_id'],
        'name' => $b['barangay_name'],
        'username' => $b['username'],
        'email' => '',
        'invite_pending' => false,
        'role' => account_role_name($M_BARANGAY, (int) $b['barangay_id']),
        'barangay' => true,
        'status' => $b['disaster_open'] ? 'Open' : 'Closed',
        'disaster_open' => (bool) $b['disaster_open'],
        'address' => $b['address'] ?: '',
        'perms' => account_perm_names($M_BARANGAY, (int) $b['barangay_id']),
        'is_me' => false,
    ];
}

$registered = array_map('strtolower', array_map('trim', array_column($barangays, 'barangay_name')));
$availableBarangays = $OFFICIAL_BARANGAYS;

$tabs = ['admin' => [], 'barangay' => [], 'user' => []];
foreach ($rows as $r) {
    $tabs[$r['role']][] = $r;
}
function cmpn($x, $y) { return strcmp(strtolower($x['name']), strtolower($y['name'])); }
foreach ($tabs as $k => $list) { usort($tabs[$k], 'cmpn'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Roles &amp; Permissions - Admin Panel</title>
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
.header h1 i{margin-right:8px;}
.admin-profile{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.25);}
.admin-profile img{width:36px;height:36px;border-radius:50%;border:2px solid white;object-fit:cover;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.page-wrap{max-width:1280px;margin:0 auto;padding:28px 30px;}
.page-grid{display:grid;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:9px;font-size:1.02rem;color:#202124;margin-bottom:6px;}
.card-title i{color:#0072C6;width:22px;height:22px;border-radius:7px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.8rem;}
.card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert.ok{background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-left:4px solid #c5221f;}
.alert.stack{flex-direction:column;align-items:flex-start;gap:8px;}
.alert.stack .invite-link{word-break:break-all;font-size:.9rem;color:#b02a37;background:#fff;border:1px dashed #dc3545;border-radius:8px;padding:8px 10px;width:100%;}
.form-group{margin-bottom:16px;position:relative;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group label small{font-weight:400;color:#8a97a8;}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;font-family:inherit;background:#fff;}
.form-group input:focus,.form-group select:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.form-group input[readonly]{background:#f4f6f8;color:#5f6368;}
.form-field{display:none;}
.form-field.show{display:block;}
.btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;font-family:inherit;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
.request-label{font-size:.78rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.4px;margin:14px 0 6px;}
.radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.radio-option{flex:1;min-width:105px;padding:10px 12px;border:2px solid #e0e0e0;border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;font-family:inherit;}
.radio-option:hover{border-color:#0072C6;}
.radio-option.selected{border-color:#0072C6;background:#e8f2fb;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.radio-option.disabled{opacity:.45;cursor:not-allowed;}
.radio-option input{display:none;}
.radio-option .r-icon{font-size:1.3rem;display:block;margin-bottom:4px;}
.radio-option .r-label{font-size:.8rem;font-weight:700;color:#333;display:block;}
.radio-option .r-desc{font-size:.65rem;color:#8a97a8;display:block;margin-top:2px;}
.role-note{font-size:.8rem;color:#5f6368;background:#f8f9fa;border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;margin-bottom:14px;}
.perm-hint{font-size:.72rem;color:#8a97a8;margin:2px 0 10px;}
.tabs{display:flex;gap:8px;margin-bottom:16px;border-bottom:2px solid #eef0f3;}
.tab-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:none;background:transparent;font-size:.9rem;font-weight:600;color:#5f6368;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;font-family:inherit;}
.tab-btn:hover{color:#0072C6;}
.tab-btn.active{color:#0072C6;border-bottom-color:#0072C6;}
.tab-btn .cnt{font-size:.7rem;font-weight:700;background:#f1f3f4;color:#5f6368;border-radius:12px;padding:1px 8px;}
.tab-btn.active .cnt{background:#e8f2fb;color:#0072C6;}
.tab-panel{display:none;}
.tab-panel.show{display:block;}
.adm-table{width:100%;border-collapse:collapse;}
.adm-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#8a97a8;padding:10px;border-bottom:2px solid #eef0f3;font-weight:700;white-space:nowrap;}
.adm-table td{padding:11px 10px;border-bottom:1px solid #f0f2f5;font-size:.88rem;color:#333;vertical-align:middle;}
.adm-table tr:last-child td{border-bottom:none;}
.adm-table tr:hover td{background:#fafcff;}
.user-cell{display:flex;align-items:center;gap:10px;font-weight:600;min-width:130px;}
.avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0a6cff,#00a3ff);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;}
.avatar.bar{background:linear-gradient(135deg,#28a745,#0a6cff);}
.avatar.usr{background:linear-gradient(135deg,#f9a825,#ef6c00);}
.you-badge{font-size:.62rem;font-weight:700;background:#e8f2fb;color:#0072C6;padding:2px 8px;border-radius:12px;margin-left:4px;white-space:nowrap;}
.mono{font-family:Consolas,monospace;font-size:.82rem;color:#202124;}
.role-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:600;color:#5f6368;background:#f1f3f4;padding:4px 12px;border-radius:20px;}
.role-badge.admin{background:#e8f2fb;color:#0072C6;}
.role-badge.barangay{background:#e8f7ee;color:#28a745;}
.role-badge.user{background:#fff3cd;color:#856404;}
.status-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:20px;}
.status-badge.active{background:#e6f4ea;color:#188038;}
.status-badge.open{background:#fff3cd;color:#856404;}
.status-badge.closed{background:#f1f3f4;color:#5f6368;}
.status-badge.pending{background:#fdeaea;color:#dc3545;}
.actions{display:flex;gap:8px;align-items:center;white-space:nowrap;justify-content:flex-end;}
.btn-edit,.btn-del,.btn-tgl,.btn-now{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:8px;padding:7px 12px;font-size:.76rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-edit{background:#e8f2fb;color:#0072C6;}
.btn-edit:hover{background:#0072C6;color:#fff;}
.btn-del{background:#fdeaea;color:#dc3545;}
.btn-del:hover{background:#dc3545;color:#fff;}
.btn-tgl{background:#fff8e1;color:#b76e00;}
.btn-tgl.closed{background:#f1f3f4;color:#5f6368;}
.btn-tgl:hover{background:#b76e00;color:#fff;}
.btn-now{background:#e8f7ee;color:#28a745;}
.btn-now:hover{background:#28a745;color:#fff;}
.empty{text-align:center;color:#8a97a8;padding:28px;}
.empty i{font-size:2rem;margin-bottom:8px;display:block;}
.modal{display:none;position:fixed;inset:0;background:rgba(15,30,50,.5);z-index:1100;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:620px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;max-height:90vh;overflow-y:auto;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#0072C6;width:28px;height:28px;border-radius:8px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-save:hover{box-shadow:0 4px 14px rgba(0,114,198,.3);}
.perms-section{margin-top:16px;border-top:1px solid #eef0f3;padding-top:14px;}
.perms-title{font-weight:700;font-size:.9rem;color:#202124;margin-bottom:4px;}
.perms-sub{font-size:.75rem;color:#8a97a8;margin-bottom:10px;}
.perm-group{margin-bottom:12px;}
.perm-group-title{font-size:.75rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #e0e0e0;}
.perm-item{display:flex;align-items:center;gap:10px;padding:5px 8px;border-radius:6px;margin-bottom:1px;}
.perm-item:hover{background:#f8fafc;}
.perm-item input[type="checkbox"]{width:16px;height:16px;accent-color:#0072C6;cursor:pointer;flex-shrink:0;}
.perm-item label{font-size:.83rem;color:#333;cursor:pointer;display:flex;align-items:center;gap:8px;flex:1;}
.perm-item input:disabled + label{cursor:not-allowed;}
.required-star{color:#dc3545;font-weight:800;}
.perm-state{font-size:.66rem;padding:2px 8px;border-radius:10px;font-weight:600;white-space:nowrap;}
.perm-state.required{background:#fdeaea;color:#dc3545;}
.perm-state.optional{background:#e8f2fb;color:#0072C6;}
.create-perms{padding-right:4px;}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> Roles &amp; Permissions</h1>
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
            <?php if ($message != ''): ?>
                <div class="alert <?php echo $msgType === 'ok' ? 'ok' : 'err'; ?> <?php echo $msgType === 'ok' && strpos($message, 'Invite link') !== false ? 'stack' : ''; ?>">
                    <span><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div class="page-grid">
                <!-- ============ EXISTING ACCOUNTS ============ -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-users"></i> Existing Accounts</div>
                    <p class="card-sub">View, edit, or delete accounts. Edit to adjust roles and individual permissions.</p>

                    <div class="tabs">
                        <button type="button" class="tab-btn active" data-tab="admin" onclick="switchTab('admin')"><i class="fas fa-user-shield"></i> Admin <span class="cnt"><?php echo count($tabs['admin']); ?></span></button>
                        <button type="button" class="tab-btn" data-tab="barangay" onclick="switchTab('barangay')"><i class="fas fa-building"></i> Barangay <span class="cnt"><?php echo count($tabs['barangay']); ?></span></button>
                        <button type="button" class="tab-btn" data-tab="user" onclick="switchTab('user')"><i class="fas fa-user"></i> User <span class="cnt"><?php echo count($tabs['user']); ?></span></button>
                    </div>

                    <?php foreach (['admin', 'barangay', 'user'] as $tkey): ?>
                    <div class="tab-panel <?php echo $tkey === 'admin' ? 'show' : ''; ?>" id="panel-<?php echo $tkey; ?>">
                        <?php if (count($tabs[$tkey]) === 0): ?>
                            <div class="empty"><i class="fas fa-user-shield"></i>No <?php echo ucfirst($tkey); ?> accounts yet.</div>
                        <?php else: ?>
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>#</th><th>Name</th><th>Username</th><th>Barangay</th><th>Role</th><th>Status</th>
                                    <th style="text-align:right;">Permissions</th><th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tabs[$tkey] as $i => $a): ?>
                                <?php $permCount = count($a['perms']); ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td>
                                        <span class="user-cell">
                                            <span class="avatar <?php echo $a['type'] === 'barangay' ? 'bar' : ($a['role'] === 'user' ? 'usr' : ''); ?>"><?php echo htmlspecialchars(strtoupper(substr($a['name'], 0, 1))); ?></span>
                                            <?php echo htmlspecialchars($a['name']); ?>
                                            <?php if ($a['is_me']): ?><span class="you-badge">You</span><?php endif; ?>
                                        </span>
                                    </td>
                                    <td><span class="mono"><?php echo $a['username'] === null ? '— (pending)' : htmlspecialchars($a['username']); ?></span></td>
                                    <td><?php echo $a['barangay'] ? htmlspecialchars($a['name']) : '<span style="color:#b0b6bd;">—</span>'; ?></td>
                                    <td><span class="role-badge <?php echo $a['role']; ?>"><i class="fas fa-<?php echo ($a['role'] === 'admin' ? 'user-shield' : ($a['role'] === 'barangay' ? 'building' : 'user')); ?>"></i> <?php echo ucfirst($a['role']); ?></span></td>
                                    <td>
                                        <?php if ($a['type'] === 'barangay'): ?>
                                            <span class="status-badge <?php echo $a['disaster_open'] ? 'open' : 'closed'; ?>"><i class="fas fa-<?php echo $a['disaster_open'] ? 'house-fire' : 'bed'; ?>"></i> <?php echo $a['status']; ?></span>
                                        <?php elseif ($a['invite_pending']): ?>
                                            <span class="status-badge pending"><i class="fas fa-envelope-open-text"></i> Invited</span>
                                        <?php else: ?>
                                            <span class="status-badge active"><i class="fas fa-check-circle"></i> Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;"><span style="font-size:.78rem;color:#5f6368;"><?php echo $permCount; ?> perm<?php echo $permCount !== 1 ? 's' : ''; ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn-edit" onclick="openEdit(<?php echo htmlspecialchars(json_encode($a), ENT_QUOTES); ?>)"><i class="fas fa-pen"></i> Edit</button>
                                            <?php if ($a['type'] === 'barangay'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_disaster">
                                                <input type="hidden" name="barangay_id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="btn-tgl <?php echo $a['disaster_open'] ? '' : 'closed'; ?>" title="Toggle disaster operation status"><i class="fas fa-exclamation-triangle"></i> <?php echo $a['disaster_open'] ? 'Close' : 'Open'; ?></button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if ($a['role'] === 'user' && $a['invite_pending']): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="resend_invite">
                                                <input type="hidden" name="account_id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="btn-now" title="Regenerate and resend the sign-up link"><i class="fas fa-paper-plane"></i> Send Invite</button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if (!$a['is_me']): ?>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this <?php echo $a['type']; ?> account? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="account_type" value="<?php echo $a['type']; ?>">
                                                <input type="hidden" name="account_id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- ============ CREATE ACCOUNT ============ -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-user-plus"></i> Create Account</div>
                    <p class="card-sub">Create an Admin, Barangay, or read-only User account. Required permissions are locked per role. Users are invited through email and sign up on their own.</p>

                    <form method="POST" action="" id="createForm">
                        <input type="hidden" name="action" value="add">

                        <div class="form-group">
                            <label>Account Type</label>
                            <div class="radio-group">
                                <label class="radio-option selected" data-type="admin">
                                    <input type="radio" name="account_type" value="admin" checked>
                                    <span class="r-icon">🛡️</span>
                                    <span class="r-label">Admin</span>
                                    <span class="r-desc">Full MSWD access</span>
                                </label>
                                <label class="radio-option" data-type="barangay">
                                    <input type="radio" name="account_type" value="barangay">
                                    <span class="r-icon">🏘️</span>
                                    <span class="r-label">Barangay</span>
                                    <span class="r-desc">Barangay of Malilipot</span>
                                </label>
                                <label class="radio-option" data-type="user">
                                    <input type="radio" name="account_type" value="user">
                                    <span class="r-icon">👤</span>
                                    <span class="r-label">User</span>
                                    <span class="r-desc">Invite by email (read-only)</span>
                                </label>
                            </div>
                        </div>

                        <div id="fieldCred" class="form-field show">
                            <div class="form-group" id="grpCreateName">
                                <label>Full Name</label>
                                <input type="text" name="name" id="createName" placeholder="Full name" autocomplete="off">
                            </div>
                            <div class="form-group" id="grpCreateUsername">
                                <label>Username</label>
                                <input type="text" name="username" id="createUsername" placeholder="Login username" autocomplete="off">
                            </div>
                            <div class="form-group" id="grpCreateEmail" style="display:none;">
                                <label>Email <small>(invitation will be sent here)</small></label>
                                <input type="email" name="email" id="createEmail" placeholder="invitee@example.com" autocomplete="off">
                            </div>
                        </div>

                        <div id="fieldBarangay" class="form-field">
                            <div class="form-group">
                                <label>Barangay of Malilipot <small>(18 official barangays)</small></label>
                                <select name="barangay_name" id="createBarangay">
                                    <option value="">-- Select a barangay --</option>
                                    <?php foreach ($availableBarangays as $b): ?>
                                        <?php $taken = in_array(strtolower($b), $registered, true); ?>
                                        <option value="<?php echo $taken ? '' : htmlspecialchars($b); ?>" <?php echo $taken ? 'disabled' : ''; ?>><?php echo htmlspecialchars($b); ?><?php echo $taken ? ' (already registered)' : ''; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Auto-generated Username</label>
                                <input type="text" name="barangay_username" id="createBarangayUsername" readonly placeholder="(auto-generated)">
                            </div>
                            <div class="form-group">
                                <label>Full Address <small>(optional)</small></label>
                                <input type="text" name="address" placeholder="Enter full address">
                            </div>
                        </div>

                        <div class="form-group" id="grpCreatePassword">
                            <label>Password <small>(not needed for Users — they set it at sign-up)</small></label>
                            <input type="password" name="password" id="createPassword" placeholder="Min. 6 characters" autocomplete="new-password">
                        </div>
                        <div class="form-group" id="grpCreateConfirm">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" id="createConfirm" placeholder="Confirm password" autocomplete="new-password">
                        </div>

                        <div class="request-label"><i class="fas fa-key" style="margin-right:6px;"></i> Permissions for this role</div>
                        <div class="role-note" id="createRoleNote"><i class="fas fa-shield-alt"></i> <span id="createRoleNoteText">Admin</span></div>

                        <div class="perms-section">
                            <div class="perms-title">Permission Checklist</div>
                            <div class="perms-sub">Defaults are pre-checked. <span style="color:#dc3545;font-weight:700;">* Required</span> for the role (cannot be unchecked).</div>
                            <div class="create-perms">
                                <?php foreach ($GROUPS as $gname => $pnames): ?>
                                    <div class="perm-group">
                                        <div class="perm-group-title"><?php echo htmlspecialchars($gname); ?></div>
                                        <?php foreach ($pnames as $pname): ?>
                                            <div class="perm-item">
                                                <input type="checkbox" name="permissions[<?php echo $pname; ?>]" value="1" id="create-perm-<?php echo $pname; ?>" data-perm="<?php echo $pname; ?>">
                                                <label for="create-perm-<?php echo $pname; ?>"><span class="perm-name"><?php echo htmlspecialchars($PERMISSIONS[$pname]); ?></span><span class="required-star" id="create-star-<?php echo $pname; ?>" style="display:none;">*</span></label>
                                                <span class="perm-state optional" id="create-state-<?php echo $pname; ?>">Optional</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn" style="margin-top:14px;"><i class="fas fa-save"></i> Save Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ EDIT ACCOUNT + PERMISSIONS MODAL ============ -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-user-edit"></i> Edit Account &amp; Permissions</div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="account_type" id="editType">
            <input type="hidden" name="account_id" id="editId">

            <div class="form-group" id="fieldEditName">
                <label>Full Name</label>
                <input type="text" name="name" id="editName" autocomplete="off">
            </div>
            <div class="form-group" id="fieldEditUsername">
                <label>Username</label>
                <input type="text" name="username" id="editUsername" autocomplete="off">
            </div>
            <div class="form-group" id="fieldEditEmail" style="display:none;">
                <label>Email</label>
                <input type="email" name="email" id="editEmail" autocomplete="off">
            </div>

            <div class="form-group" id="fieldEditBarangay" style="display:none;">
                <label>Barangay of Malilipot</label>
                <select name="barangay_name" id="editBarangayName"></select>
            </div>
            <div class="form-group" id="fieldEditBrgyUsername" style="display:none;">
                <label>Auto-generated Username</label>
                <input type="text" name="barangay_username" id="editBrgyUsername" readonly>
            </div>
            <div class="form-group" id="fieldEditAddress" style="display:none;">
                <label>Full Address</label>
                <input type="text" name="address" id="editAddress" autocomplete="off">
            </div>

            <div class="form-group">
                <label>New Password <small>(leave blank to keep current)</small></label>
                <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="editRole">Role</label>
                <select name="role" id="editRole" onchange="updateEditPerms()"></select>
            </div>

            <div class="perms-section">
                <div class="perms-title"><i class="fas fa-key" style="color:#0072C6;margin-right:6px;"></i>Permissions</div>
                <div class="perms-sub">Check the permissions for this account. <span style="color:#dc3545;font-weight:700;">* Required</span> for the selected role (cannot be unchecked).</div>
                <div class="create-perms">
                    <?php foreach ($GROUPS as $gname => $pnames): ?>
                        <div class="perm-group">
                            <div class="perm-group-title"><?php echo htmlspecialchars($gname); ?></div>
                            <?php foreach ($pnames as $pname): ?>
                                <div class="perm-item">
                                    <input type="checkbox" name="permissions[<?php echo $pname; ?>]" value="1" id="edit-perm-<?php echo $pname; ?>" data-perm="<?php echo $pname; ?>">
                                    <label for="edit-perm-<?php echo $pname; ?>"><span class="perm-name"><?php echo htmlspecialchars($PERMISSIONS[$pname]); ?></span><span class="required-star" id="edit-star-<?php echo $pname; ?>" style="display:none;">*</span></label>
                                    <span class="perm-state optional" id="edit-state-<?php echo $pname; ?>">Optional</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show"); }

const REQUIRED = <?php echo json_encode($REQUIRED); ?>;
const DEFAULTS = <?php echo json_encode($DEFAULTS); ?>;
const ALL_PERMS = <?php echo json_encode(array_keys($PERMISSIONS)); ?>;
const TYPE_ROLE = { admin: 'admin', barangay: 'barangay', user: 'user' };
const ALLOWED_ROLES = { admin: ['admin','user'], barangay: ['barangay','user'] };
const AVAILABLE_BRGY = <?php echo json_encode($availableBarangays); ?>;
const REGISTERED_BRGY = <?php echo json_encode(array_map('strtolower', $registered)); ?>;
const isBrgyTaken = (name) => REGISTERED_BRGY.includes(String(name).toLowerCase());

let createType = 'admin';

function applyCreatePerms(){
    const role = TYPE_ROLE[createType];
    const req = REQUIRED[role] || [];
    const def = DEFAULTS[role] || [];
    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('create-perm-' + perm);
        const star = document.getElementById('create-star-' + perm);
        const state = document.getElementById('create-state-' + perm);
        const isReq = req.indexOf(perm) !== -1;
        cb.disabled = isReq;
        cb.checked = isReq || def.indexOf(perm) !== -1;
        star.style.display = isReq ? 'inline' : 'none';
        state.textContent = isReq ? 'Required' : 'Optional';
        state.className = isReq ? 'perm-state required' : 'perm-state optional';
    });
    const labels = {
        admin: 'Role: Admin — full MSWD access. Key management permissions are required.',
        barangay: 'Role: Barangay — barangay-level duties. Reporting & disaster permissions are required.',
        user: 'Role: User — invited by email. They sign up with their own username and password, and get read-only/view permissions.'
    };
    document.getElementById('createRoleNoteText').textContent = labels[role] || role;
}

function setCreateType(value){
    createType = value;
    document.querySelectorAll('#createForm .radio-option').forEach(o => { o.classList.toggle('selected', o.dataset.type === value); });
    const isCred = value === 'admin' || value === 'user';
    document.getElementById('fieldCred').classList.toggle('show', isCred);
    document.getElementById('fieldBarangay').classList.toggle('show', value === 'barangay');
    document.getElementById('grpCreateName').style.display = isCred ? '' : 'none';
    document.getElementById('grpCreateUsername').style.display = value === 'admin' ? '' : 'none';
    document.getElementById('grpCreateEmail').style.display = value === 'user' ? '' : 'none';
    document.getElementById('grpCreatePassword').style.display = value === 'user' ? 'none' : '';
    document.getElementById('grpCreateConfirm').style.display = value === 'user' ? 'none' : '';
    document.getElementById('createName').required = isCred;
    document.getElementById('createUsername').required = value === 'admin';
    document.getElementById('createEmail').required = value === 'user';
    document.getElementById('createPassword').required = value !== 'user';
    document.getElementById('createConfirm').required = value !== 'user';
    document.getElementById('createBarangay').required = value === 'barangay';
    applyCreatePerms();
}
document.querySelectorAll('#createForm .radio-option').forEach(o => {
    o.addEventListener('click', function(){
        if (o.classList.contains('disabled')) return;
        const r = o.querySelector('input'); if (r) r.checked = true;
        setCreateType(o.dataset.type);
    });
});

function slugify(name){ return name.replace(/ /g, '_'); }
const brgyInput = document.getElementById('createBarangay');
if (brgyInput) brgyInput.addEventListener('change', function(){
    document.getElementById('createBarangayUsername').value = this.value ? slugify(this.value) : '';
});

function switchTab(key){
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === key));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('show'));
    document.getElementById('panel-' + key).classList.add('show');
}

function updateEditPerms(){
    const role = document.getElementById('editRole').value;
    const req = REQUIRED[role] || [];
    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        const star = document.getElementById('edit-star-' + perm);
        const state = document.getElementById('edit-state-' + perm);
        const isReq = req.indexOf(perm) !== -1;
        if (isReq){ cb.checked = true; cb.disabled = true; star.style.display = 'inline'; state.textContent = 'Required'; state.className = 'perm-state required'; }
        else { cb.disabled = false; star.style.display = 'none'; state.textContent = 'Optional'; state.className = 'perm-state optional'; }
    });
}

function openEdit(row){
    const isBarangay = row.type === 'barangay';
    const isUser = row.type === 'admin' && row.role === 'user';
    document.getElementById('editType').value = row.type;
    document.getElementById('editId').value = row.id;

    document.getElementById('fieldEditName').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditUsername').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditEmail').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditBarangay').style.display = isBarangay ? '' : 'none';
    document.getElementById('fieldEditBrgyUsername').style.display = isBarangay ? '' : 'none';
    document.getElementById('fieldEditAddress').style.display = isBarangay ? '' : 'none';

    if (isBarangay){
        const sel = document.getElementById('editBarangayName');
        sel.innerHTML = '';
        const opts = AVAILABLE_BRGY.filter(b => !isBrgyTaken(b) || b === row.name);
        opts.forEach(b => { const o = document.createElement('option'); o.value = b; o.textContent = b; sel.appendChild(o); });
        sel.value = row.name;
        document.getElementById('editBrgyUsername').value = row.username || slugify(row.name);
        document.getElementById('editAddress').value = row.address || '';
        document.getElementById('editName').value = '';
        document.getElementById('editUsername').value = '';
        document.getElementById('editEmail').value = '';
    } else {
        document.getElementById('editName').value = row.name;
        document.getElementById('editUsername').value = row.username || '';
        document.getElementById('editEmail').value = row.email || '';
        document.getElementById('editEmail').required = isUser;
        document.getElementById('editUsername').required = !(isUser && row.invite_pending);
        document.getElementById('editBarangayName').innerHTML = '';
        document.getElementById('editAddress').value = '';
    }

    const selRole = document.getElementById('editRole');
    selRole.innerHTML = '';
    ALLOWED_ROLES[row.type].forEach(r => {
        const o = document.createElement('option');
        o.value = r; o.textContent = r.charAt(0).toUpperCase() + r.slice(1);
        selRole.appendChild(o);
    });
    selRole.value = ALLOWED_ROLES[row.type].indexOf(row.role) !== -1 ? row.role : ALLOWED_ROLES[row.type][0];

    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        cb.checked = (row.perms || []).indexOf(perm) !== -1;
        cb.disabled = false;
    });
    updateEditPerms();
    document.getElementById('editModal').classList.add('show');
}

const editBrgySel = document.getElementById('editBarangayName');
if (editBrgySel) editBrgySel.addEventListener('change', function(){
    document.getElementById('editBrgyUsername').value = slugify(this.value);
});

function closeEdit(){ document.getElementById('editModal').classList.remove('show'); }
document.getElementById('editModal').addEventListener('click', function(e){ if (e.target === this) closeEdit(); });

// init
applyCreatePerms();
switchTab('admin');
</script>
</body>
</html>