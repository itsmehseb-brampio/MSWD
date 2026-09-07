<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $sql = "SELECT a.*, ad.username as admin_name FROM announcements a LEFT JOIN admins ad ON a.admin_id = ad.id ORDER BY a.is_pinned DESC, a.created_at DESC";
        $result = $conn->query($sql);
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $aid = intval($row['announcement_id']);
            $targets = $conn->query("SELECT b.barangay_id, b.barangay_name FROM announcement_targets at2 JOIN barangays b ON at2.barangay_id = b.barangay_id WHERE at2.announcement_id = $aid");
            $row['targets'] = [];
            $row['target_all'] = true;
            if ($targets->num_rows > 0) {
                $row['target_all'] = false;
                while ($t = $targets->fetch_assoc()) {
                    $row['targets'][] = $t;
                }
            }
            $list[] = $row;
        }
        echo json_encode($list);
        break;

    case 'post':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $admin_id = intval($_SESSION['admin_id']);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $is_pinned = intval($_POST['is_pinned'] ?? 0);
        $target_all = intval($_POST['target_all'] ?? 1);
        $target_ids = $_POST['target_ids'] ?? [];
        if (!$title || !$message) { echo json_encode(['error' => 'Title and message required']); exit(); }

        $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, message, is_pinned) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $admin_id, $title, $message, $is_pinned);
        $stmt->execute();
        $ann_id = $conn->insert_id;

        if (!$target_all && !empty($target_ids)) {
            $stmt2 = $conn->prepare("INSERT IGNORE INTO announcement_targets (announcement_id, barangay_id) VALUES (?, ?)");
            foreach ($target_ids as $bid) {
                $bid = intval($bid);
                if ($bid > 0) {
                    $stmt2->bind_param("ii", $ann_id, $bid);
                    $stmt2->execute();
                }
            }
        }

        echo json_encode(['ok' => true, 'id' => $ann_id]);
        break;

    case 'delete':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid']); exit(); }
        $conn->query("DELETE FROM announcement_targets WHERE announcement_id=$id");
        $conn->query("DELETE FROM announcements WHERE announcement_id=$id");
        echo json_encode(['ok' => true]);
        break;

    case 'pin':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $id = intval($_POST['id'] ?? 0);
        $pinned = intval($_POST['pinned'] ?? 0);
        $conn->query("UPDATE announcements SET is_pinned=$pinned WHERE announcement_id=$id");
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
