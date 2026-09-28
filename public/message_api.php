<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require 'db.php';
session_write_close();

$role = $_GET['role'] ?? $_POST['role'] ?? '';
$is_admin = ($role === 'admin') && isset($_SESSION['admin_id']);
$is_brgy = ($role === 'barangay') && isset($_SESSION['barangay_id']);

if (!$is_admin && !$is_brgy) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_type = $is_admin ? 'admin' : 'barangay';
$user_id = $is_admin ? intval($_SESSION['admin_id']) : intval($_SESSION['barangay_id']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$admin_display_name = 'DSWD - Municipal Office';
$admin_logo_url = 'mapa.png';

function brgyLogoUrl($path) {
    if (!$path) return '';
    if (strpos($path, 'uploads/') === 0) {
        return '/barangay/' . $path;
    }
    return $path;
}

switch ($action) {
    case 'heartbeat':
        if ($is_brgy) {
            $conn->query("UPDATE barangays SET last_active=NOW() WHERE barangay_id=$user_id");
        } else {
            $conn->query("UPDATE barangays SET last_active=NULL WHERE last_active < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        }
        echo json_encode(['ok' => true]);
        break;

    /* ---- GROUP CHAT ---- */

    case 'group_accounts':
        $accounts = [];

        $accounts[] = ['id' => 0, 'name' => 'DSWD - Municipal Office', 'logo' => 'mapa.png', 'is_online' => 1, 'sub' => 'MSWD Employee', 'is_me' => $is_admin ? 1 : 0, 'address' => 'Municipality of Malilipot'];

        $sql = "SELECT b.barangay_id, b.barangay_name, b.address, b.last_active, bd.logo,
                TIMESTAMPDIFF(SECOND, b.last_active, NOW()) as sec_ago
                FROM barangays b
                LEFT JOIN barangay_details bd ON bd.barangay_id = b.barangay_id
                ORDER BY b.barangay_name ASC";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            if ($is_brgy && intval($row['barangay_id']) === $user_id) continue;
            $row['id'] = intval($row['barangay_id']);
            $row['name'] = $row['barangay_name'];
            $row['is_me'] = ($is_brgy && intval($row['barangay_id']) === $user_id) ? 1 : 0;
            $row['is_online'] = ($row['last_active'] && $row['sec_ago'] < 120) ? 1 : 0;
            $row['sub'] = $row['is_online'] ? 'Online' : ($row['last_active'] ? 'Last active ' . date('M d, H:i', strtotime($row['last_active'])) : 'Offline');
            $row['logo'] = brgyLogoUrl($row['logo']);
            $accounts[] = $row;
        }

        if ($is_brgy) {
            $my = $conn->query("SELECT b.barangay_name, bd.logo, b.last_active,
                TIMESTAMPDIFF(SECOND, b.last_active, NOW()) as sec_ago
                FROM barangays b LEFT JOIN barangay_details bd ON bd.barangay_id=b.barangay_id
                WHERE b.barangay_id=$user_id")->fetch_assoc();
            $me = [
                'id' => $user_id,
                'name' => $my ? $my['barangay_name'] : 'Barangay',
                'logo' => brgyLogoUrl($my['logo'] ?? ''),
                'is_online' => ($my && $my['last_active'] && $my['sec_ago'] < 120) ? 1 : 0,
                'sub' => 'You',
                'is_me' => 1,
                'address' => ''
            ];
            $accounts = array_merge([$me], $accounts);
        }

        echo json_encode($accounts);
        break;

    case 'group_fetch':
        $brgys = [];
        $r = $conn->query("SELECT b.barangay_id, b.barangay_name, bd.logo FROM barangays b LEFT JOIN barangay_details bd ON bd.barangay_id=b.barangay_id");
        while ($row = $r->fetch_assoc()) {
            $row['logo_url'] = brgyLogoUrl($row['logo']);
            $brgys[intval($row['barangay_id'])] = $row;
        }
        $r = $conn->query("SELECT * FROM group_messages ORDER BY gm_id ASC");
        $msgs = [];
        while ($row = $r->fetch_assoc()) {
            if ($row['sender_type'] == 'admin') {
                $row['sender_name'] = $admin_display_name;
                $row['sender_logo'] = $admin_logo_url;
                $row['own'] = ($user_type === 'admin') ? 1 : 0;
            } else {
                $b = $brgys[intval($row['sender_id'])] ?? null;
                $row['sender_name'] = $b ? $b['barangay_name'] : 'Barangay';
                $row['sender_logo'] = $b ? $b['logo_url'] : '';
                $row['own'] = ($user_type === 'barangay' && intval($row['sender_id']) === $user_id) ? 1 : 0;
            }
            $msgs[] = $row;
        }
        echo json_encode(['messages' => $msgs, 'member_count' => (count($brgys) + 1)]);
        break;

    case 'group_send':
        $message = trim($_POST['message'] ?? '');
        if (!$message) { echo json_encode(['error' => 'Empty message']); exit(); }
        $message = mb_substr($message, 0, 2000);

        $stmt = $conn->prepare("INSERT INTO group_messages (sender_type, sender_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $user_type, $user_id, $message);
        $stmt->execute();
        echo json_encode(['ok' => true, 'message_id' => $conn->insert_id]);
        break;

    /* ---- legacy 1-on-1 actions (admin only) ---- */

    case 'list':
        if ($is_brgy) { echo json_encode([]); exit(); }
        $sql = "SELECT b.barangay_id, b.barangay_name, b.address, b.last_active, bd.logo,
                TIMESTAMPDIFF(SECOND, b.last_active, NOW()) as sec_ago
                FROM barangays b
                LEFT JOIN barangay_details bd ON bd.barangay_id = b.barangay_id
                ORDER BY b.barangay_name ASC";
        $result = $conn->query($sql);
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $bid = intval($row['barangay_id']);
            $last_msg = $conn->query("SELECT message, created_at FROM messages WHERE (sender_type='barangay' AND sender_id=$bid AND receiver_type='admin' AND receiver_id=$user_id) OR (sender_type='admin' AND sender_id=$user_id AND receiver_type='barangay' AND receiver_id=$bid) ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
            $unread_row = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE sender_type='barangay' AND sender_id=$bid AND receiver_type='admin' AND receiver_id=$user_id AND is_read=0")->fetch_assoc();
            $row['last_message'] = $last_msg ? $last_msg['message'] : null;
            $row['last_time'] = $last_msg ? $last_msg['created_at'] : null;
            $row['unread'] = intval($unread_row['cnt']);
            $row['is_online'] = ($row['last_active'] && $row['sec_ago'] < 120) ? 1 : 0;
            $row['logo_url'] = brgyLogoUrl($row['logo']);
            $list[] = $row;
        }
        echo json_encode($list);
        break;

    case 'fetch':
        if ($is_brgy) { echo json_encode(['messages' => []]); exit(); }
        $barangay_id = intval($_GET['barangay_id'] ?? 0);
        if (!$barangay_id) { echo json_encode(['messages' => [], 'barangay' => null]); exit(); }

        $conn->query("UPDATE messages SET is_read=1 WHERE sender_type='barangay' AND sender_id=$barangay_id AND receiver_type='admin' AND receiver_id=$user_id AND is_read=0");

        $binfo = $conn->query("SELECT b.barangay_name, bd.logo FROM barangays b LEFT JOIN barangay_details bd ON bd.barangay_id=b.barangay_id WHERE b.barangay_id=$barangay_id")->fetch_assoc();
        $bname = $binfo ? $binfo['barangay_name'] : 'Barangay';
        $blogo = $binfo ? brgyLogoUrl($binfo['logo']) : '';

        $sql = "SELECT * FROM messages WHERE
                (sender_type='admin' AND sender_id=$user_id AND receiver_type='barangay' AND receiver_id=$barangay_id)
                OR (sender_type='barangay' AND sender_id=$barangay_id AND receiver_type='admin' AND receiver_id=$user_id)
                ORDER BY created_at ASC";
        $result = $conn->query($sql);
        $msgs = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['sender_type'] == 'admin') {
                $row['sender_name'] = $admin_display_name;
                $row['sender_logo'] = $admin_logo_url;
            } else {
                $row['sender_name'] = $bname;
                $row['sender_logo'] = $blogo;
            }
            $msgs[] = $row;
        }
        echo json_encode(['messages' => $msgs, 'barangay' => ['name' => $bname, 'logo' => $blogo]]);
        break;

    case 'send':
        if ($is_brgy) { echo json_encode(['error' => 'Not supported']); exit(); }
        $barangay_id = intval($_POST['barangay_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if (!$barangay_id || !$message) { echo json_encode(['error' => 'Invalid input']); exit(); }

        $stmt = $conn->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message) VALUES ('admin', ?, 'barangay', ?, ?)");
        $stmt->bind_param("iis", $user_id, $barangay_id, $message);
        $stmt->execute();
        echo json_encode(['ok' => true, 'message_id' => $conn->insert_id]);
        break;

    case 'unread':
        if ($is_brgy) {
            $sql = "SELECT sender_id, COUNT(*) as cnt
                    FROM messages WHERE is_read=0 AND receiver_type='barangay' AND receiver_id=$user_id
                    GROUP BY sender_id";
        } else {
            $sql = "SELECT sender_id, COUNT(*) as cnt
                    FROM messages WHERE is_read=0 AND receiver_type='admin' AND receiver_id=$user_id
                    GROUP BY sender_id";
        }
        $result = $conn->query($sql);
        $unread = [];
        while ($row = $result->fetch_assoc()) {
            $unread[] = $row;
        }
        echo json_encode($unread);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
