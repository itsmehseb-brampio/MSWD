<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $result = $conn->query("SELECT * FROM relief_schedules ORDER BY distribution_date DESC, distribution_time DESC");
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $sid = intval($row['schedule_id']);
            $items = $conn->query("SELECT * FROM relief_items WHERE schedule_id=$sid");
            $row['items'] = [];
            while ($it = $items->fetch_assoc()) { $row['items'][] = $it; }

            $targets = $conn->query("SELECT b.barangay_id, b.barangay_name, COALESCE(bd.households,0) as households FROM relief_schedule_targets rst JOIN barangays b ON rst.barangay_id = b.barangay_id LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id WHERE rst.schedule_id=$sid ORDER BY b.barangay_name ASC");
            $row['target_all'] = true;
            $row['total_households'] = 0;
            $row['targets'] = [];
            if ($targets->num_rows > 0) {
                $row['target_all'] = false;
                while ($t = $targets->fetch_assoc()) {
                    $row['targets'][] = ['barangay_id'=>intval($t['barangay_id']), 'barangay_name'=>$t['barangay_name'], 'households'=>intval($t['households'])];
                    $row['total_households'] += intval($t['households']);
                }
            } else {
                $r2 = $conn->query("SELECT COALESCE(SUM(bd.households),0) as total FROM barangays b LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id");
                $row['total_households'] = $r2->fetch_assoc()['total'] ?? 0;
            }

            $reports = $conn->query("SELECT r.*, b.barangay_name FROM relief_distribution_reports r JOIN barangays b ON r.barangay_id = b.barangay_id WHERE r.schedule_id=$sid ORDER BY r.received_at DESC");
            $row['reports'] = [];
            while ($rep = $reports->fetch_assoc()) {
                $docs = $conn->query("SELECT * FROM relief_distribution_documents WHERE report_id=" . intval($rep['report_id']) . " ORDER BY document_id ASC");
                $rep['documents'] = [];
                while ($d = $docs->fetch_assoc()) { $rep['documents'][] = $d; }
                $row['reports'][] = $rep;
            }
            $list[] = $row;
        }
        echo json_encode($list);
        break;

    case 'delete_report':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid']); exit(); }
        $docs = $conn->query("SELECT file_path FROM relief_distribution_documents WHERE report_id=$id");
        while ($d = $docs->fetch_assoc()) {
            $full = __DIR__ . '/../barangay/' . $d['file_path'];
            if (is_file($full)) { @unlink($full); }
        }
        $conn->query("DELETE FROM relief_distribution_documents WHERE report_id=$id");
        $conn->query("DELETE FROM relief_distribution_reports WHERE report_id=$id");
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
