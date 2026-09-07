<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $result = $conn->query("SELECT * FROM relief_schedules ORDER BY distribution_date DESC, distribution_time DESC");
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $sid = intval($row['schedule_id']);
            $items = $conn->query("SELECT * FROM relief_items WHERE schedule_id=$sid");
            $row['items'] = [];
            while ($it = $items->fetch_assoc()) { $row['items'][] = $it; }
            $targets = $conn->query("SELECT b.barangay_name, COALESCE(bd.households,0) as households FROM relief_schedule_targets rst JOIN barangays b ON rst.barangay_id = b.barangay_id LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id WHERE rst.schedule_id=$sid");
            $row['targets'] = [];
            $row['target_all'] = true;
            $row['total_households'] = 0;
            if ($targets->num_rows > 0) {
                $row['target_all'] = false;
                while ($t = $targets->fetch_assoc()) {
                    $row['targets'][] = $t['barangay_name'];
                    $row['target_details'][] = ['name'=>$t['barangay_name'], 'households'=>intval($t['households'])];
                    $row['total_households'] += intval($t['households']);
                }
            } else {
                $r2 = $conn->query("SELECT COALESCE(SUM(bd.households),0) as total FROM barangays b LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id");
                $row['total_households'] = $r2->fetch_assoc()['total'] ?? 0;
            }
            $list[] = $row;
        }
        echo json_encode($list);
        break;

    case 'add':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['distribution_date'] ?? '';
        $time = $_POST['distribution_time'] ?? null;
        $location = trim($_POST['location'] ?? '');
        $target_all = intval($_POST['target_all'] ?? 1);
        $target_ids = $_POST['target_ids'] ?? [];
        $item_names = $_POST['item_names'] ?? [];
        $item_qty = $_POST['item_qty'] ?? [];
        $item_unit = $_POST['item_unit'] ?? [];
        if (!$title || !$date) { echo json_encode(['error' => 'Title and date required']); exit(); }

        $stmt = $conn->prepare("INSERT INTO relief_schedules (title, description, distribution_date, distribution_time, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $desc, $date, $time, $location);
        $stmt->execute();
        $sid = $conn->insert_id;

        if (!$target_all) {
            $stmt2 = $conn->prepare("INSERT IGNORE INTO relief_schedule_targets (schedule_id, barangay_id) VALUES (?, ?)");
            foreach ($target_ids as $bid) {
                $bid = intval($bid);
                if ($bid > 0) { $stmt2->bind_param("ii", $sid, $bid); $stmt2->execute(); }
            }
        }

        $stmt3 = $conn->prepare("INSERT INTO relief_items (schedule_id, item_name, quantity, unit) VALUES (?, ?, ?, ?)");
        for ($i = 0; $i < count($item_names); $i++) {
            $name = trim($item_names[$i] ?? '');
            $qty = intval($item_qty[$i] ?? 0);
            $unit = trim($item_unit[$i] ?? 'packs');
            if ($name) {
                $stmt3->bind_param("isis", $sid, $name, $qty, $unit);
                $stmt3->execute();
            }
        }

        echo json_encode(['ok' => true, 'id' => $sid]);
        break;

    case 'update_status':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'upcoming';
        if (!in_array($status, ['upcoming','ongoing','completed'])) $status = 'upcoming';
        $conn->query("UPDATE relief_schedules SET status='$status' WHERE schedule_id=$id");
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid']); exit(); }
        $conn->query("DELETE FROM relief_items WHERE schedule_id=$id");
        $conn->query("DELETE FROM relief_schedule_targets WHERE schedule_id=$id");
        $conn->query("DELETE FROM relief_schedules WHERE schedule_id=$id");
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
