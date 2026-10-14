<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

function reliefRespond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$is_admin = isset($_SESSION['admin_id']);
$is_barangay = !$is_admin && isset($_SESSION['barangay_id']);
$barangay_id = $is_barangay ? intval($_SESSION['barangay_id']) : 0;

switch ($action) {
    case 'list':
        if (!$is_admin && !$is_barangay) {
            reliefRespond(['error' => 'Unauthorized'], 401);
        }

        if ($is_barangay) {
            $stmt = $conn->prepare(
                "SELECT s.* FROM relief_schedules s
                 WHERE NOT EXISTS (
                     SELECT 1 FROM relief_schedule_targets rst
                     WHERE rst.schedule_id = s.schedule_id
                 ) OR EXISTS (
                     SELECT 1 FROM relief_schedule_targets rst
                     WHERE rst.schedule_id = s.schedule_id AND rst.barangay_id = ?
                 )
                 ORDER BY s.distribution_date DESC, s.distribution_time DESC"
            );
            $stmt->bind_param("i", $barangay_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $barangay_stmt = $conn->prepare(
                "SELECT b.barangay_name, COALESCE(bd.households, 0) AS households
                 FROM barangays b
                 LEFT JOIN barangay_details bd ON bd.barangay_id = b.barangay_id
                 WHERE b.barangay_id = ?"
            );
            $barangay_stmt->bind_param("i", $barangay_id);
            $barangay_stmt->execute();
            $barangay = $barangay_stmt->get_result()->fetch_assoc();
            if (!$barangay) {
                reliefRespond(['error' => 'Barangay account was not found'], 404);
            }
        } else {
            $result = $conn->query(
                "SELECT * FROM relief_schedules
                 ORDER BY distribution_date DESC, distribution_time DESC"
            );
        }

        if (!$result) {
            reliefRespond(['error' => 'Unable to load relief schedules'], 500);
        }

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $sid = intval($row['schedule_id']);
            $items = $conn->query("SELECT * FROM relief_items WHERE schedule_id = $sid");
            if (!$items) {
                reliefRespond(['error' => 'Unable to load relief items'], 500);
            }
            $row['items'] = [];
            while ($item = $items->fetch_assoc()) {
                $row['items'][] = $item;
            }

            if ($is_barangay) {
                $row['barangay_name'] = $barangay['barangay_name'];
                $row['barangay_households'] = intval($barangay['households']);

                $report_stmt = $conn->prepare(
                    "SELECT * FROM relief_distribution_reports
                     WHERE schedule_id = ? AND barangay_id = ? LIMIT 1"
                );
                $report_stmt->bind_param("ii", $sid, $barangay_id);
                $report_stmt->execute();
                $report = $report_stmt->get_result()->fetch_assoc();
                $row['report'] = null;
                if ($report) {
                    $docs = $conn->query(
                        "SELECT * FROM relief_distribution_documents
                         WHERE report_id = " . intval($report['report_id']) . "
                         ORDER BY document_id ASC"
                    );
                    if (!$docs) {
                        reliefRespond(['error' => 'Unable to load report documents'], 500);
                    }
                    $report['documents'] = [];
                    while ($doc = $docs->fetch_assoc()) {
                        $report['documents'][] = $doc;
                    }
                    $row['report'] = $report;
                }
            } else {
                $targets = $conn->query(
                    "SELECT b.barangay_id, b.barangay_name,
                            COALESCE(bd.households, 0) AS households
                     FROM relief_schedule_targets rst
                     JOIN barangays b ON rst.barangay_id = b.barangay_id
                     LEFT JOIN barangay_details bd ON b.barangay_id = bd.barangay_id
                     WHERE rst.schedule_id = $sid
                     ORDER BY b.barangay_name ASC"
                );
                if (!$targets) {
                    reliefRespond(['error' => 'Unable to load relief targets'], 500);
                }
                $row['target_all'] = $targets->num_rows === 0;
                $row['targets'] = [];
                $row['total_households'] = 0;
                while ($target = $targets->fetch_assoc()) {
                    $target['barangay_id'] = intval($target['barangay_id']);
                    $target['households'] = intval($target['households']);
                    $row['targets'][] = $target;
                    $row['total_households'] += $target['households'];
                }
                if ($row['target_all']) {
                    $total = $conn->query(
                        "SELECT COALESCE(SUM(bd.households), 0) AS total
                         FROM barangays b
                         LEFT JOIN barangay_details bd ON b.barangay_id = bd.barangay_id"
                    );
                    if (!$total) {
                        reliefRespond(['error' => 'Unable to load barangay household totals'], 500);
                    }
                    $row['total_households'] = intval($total->fetch_assoc()['total']);
                }

                $reports = $conn->query(
                    "SELECT r.*, b.barangay_name
                     FROM relief_distribution_reports r
                     JOIN barangays b ON r.barangay_id = b.barangay_id
                     WHERE r.schedule_id = $sid
                     ORDER BY r.received_at DESC"
                );
                if (!$reports) {
                    reliefRespond(['error' => 'Unable to load receipt reports'], 500);
                }
                $row['reports'] = [];
                while ($report = $reports->fetch_assoc()) {
                    $docs = $conn->query(
                        "SELECT * FROM relief_distribution_documents
                         WHERE report_id = " . intval($report['report_id']) . "
                         ORDER BY document_id ASC"
                    );
                    if (!$docs) {
                        reliefRespond(['error' => 'Unable to load report documents'], 500);
                    }
                    $report['documents'] = [];
                    while ($doc = $docs->fetch_assoc()) {
                        $report['documents'][] = $doc;
                    }
                    $row['reports'][] = $report;
                }
            }
            $list[] = $row;
        }
        reliefRespond($list);

    case 'confirm':
        if (!$is_barangay) {
            reliefRespond(['error' => 'Only barangay accounts can confirm receipt'], 403);
        }

        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        $confirmed_by = trim($_POST['confirmed_by'] ?? '');
        $received_at_input = trim($_POST['received_at'] ?? '');
        $narrative = trim($_POST['narrative'] ?? '');
        $received_at = strtotime(str_replace('T', ' ', $received_at_input));
        if (!$schedule_id || !$confirmed_by || !$received_at || !$narrative) {
            reliefRespond(['error' => 'Schedule, confirmer name, receipt date, and narrative are required'], 422);
        }

        $target_stmt = $conn->prepare(
            "SELECT s.schedule_id FROM relief_schedules s
             WHERE s.schedule_id = ? AND (
                 NOT EXISTS (
                     SELECT 1 FROM relief_schedule_targets rst
                     WHERE rst.schedule_id = s.schedule_id
                 ) OR EXISTS (
                     SELECT 1 FROM relief_schedule_targets rst
                     WHERE rst.schedule_id = s.schedule_id AND rst.barangay_id = ?
                 )
             )"
        );
        $target_stmt->bind_param("ii", $schedule_id, $barangay_id);
        $target_stmt->execute();
        if (!$target_stmt->get_result()->fetch_assoc()) {
            reliefRespond(['error' => 'This relief schedule is not available to your barangay'], 404);
        }

        $existing_stmt = $conn->prepare(
            "SELECT report_id FROM relief_distribution_reports
             WHERE schedule_id = ? AND barangay_id = ? LIMIT 1"
        );
        $existing_stmt->bind_param("ii", $schedule_id, $barangay_id);
        $existing_stmt->execute();
        if ($existing_stmt->get_result()->fetch_assoc()) {
            reliefRespond(['error' => 'Receipt has already been confirmed for this schedule'], 409);
        }

        $uploads = [];
        if (isset($_FILES['documents'])) {
            $file_count = count($_FILES['documents']['name']);
            if ($file_count > 10) {
                reliefRespond(['error' => 'You can upload up to 10 documents'], 422);
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (!$finfo) {
                reliefRespond(['error' => 'Unable to validate uploaded documents'], 500);
            }
            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
            ];
            for ($i = 0; $i < $file_count; $i++) {
                $error = $_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                $tmp_name = $_FILES['documents']['tmp_name'][$i] ?? '';
                $size = intval($_FILES['documents']['size'][$i] ?? 0);
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($error !== UPLOAD_ERR_OK || !$tmp_name || $size > 10 * 1024 * 1024) {
                    finfo_close($finfo);
                    reliefRespond(['error' => 'Each document must be a valid upload no larger than 10 MB'], 422);
                }
                $mime = finfo_file($finfo, $tmp_name);
                if (!isset($allowed_types[$mime])) {
                    finfo_close($finfo);
                    reliefRespond(['error' => 'Only JPG, PNG, GIF, WEBP, and PDF documents are allowed'], 422);
                }
                $uploads[] = ['tmp_name' => $tmp_name, 'extension' => $allowed_types[$mime]];
            }
            finfo_close($finfo);
        }

        $upload_directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'relief_docs';
        if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
            reliefRespond(['error' => 'Unable to create the document upload directory'], 500);
        }

        $conn->begin_transaction();
        $report_stmt = $conn->prepare(
            "INSERT INTO relief_distribution_reports
             (schedule_id, barangay_id, confirmed_by, narrative, received_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $received_at = date('Y-m-d H:i:s', $received_at);
        $report_stmt->bind_param("iisss", $schedule_id, $barangay_id, $confirmed_by, $narrative, $received_at);
        if (!$report_stmt->execute()) {
            $conn->rollback();
            reliefRespond(['error' => 'Unable to save the receipt confirmation'], 500);
        }
        $report_id = $conn->insert_id;
        $document_stmt = $conn->prepare(
            "INSERT INTO relief_distribution_documents (report_id, file_path) VALUES (?, ?)"
        );
        $saved_files = [];
        foreach ($uploads as $upload) {
            $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
            $destination = $upload_directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                $conn->rollback();
                foreach ($saved_files as $saved_file) {
                    if (is_file($saved_file)) {
                        unlink($saved_file);
                    }
                }
                reliefRespond(['error' => 'Unable to save an uploaded document'], 500);
            }
            $saved_files[] = $destination;
            $file_path = 'uploads/relief_docs/' . $filename;
            $document_stmt->bind_param("is", $report_id, $file_path);
            if (!$document_stmt->execute()) {
                $conn->rollback();
                foreach ($saved_files as $saved_file) {
                    if (is_file($saved_file)) {
                        unlink($saved_file);
                    }
                }
                reliefRespond(['error' => 'Unable to save uploaded document details'], 500);
            }
        }
        $conn->commit();
        reliefRespond(['ok' => true, 'id' => $report_id]);

    case 'delete_report':
        if (!$is_admin) {
            reliefRespond(['error' => 'Unauthorized'], 401);
        }
        $report_id = intval($_POST['id'] ?? 0);
        if (!$report_id) {
            reliefRespond(['error' => 'Invalid report'], 422);
        }
        $docs = $conn->query(
            "SELECT file_path FROM relief_distribution_documents WHERE report_id = $report_id"
        );
        if (!$docs) {
            reliefRespond(['error' => 'Unable to load report documents'], 500);
        }
        $files = [];
        while ($doc = $docs->fetch_assoc()) {
            if (strpos($doc['file_path'], 'uploads/relief_docs/') !== 0) {
                continue;
            }
            $relative_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $doc['file_path']);
            $files[] = __DIR__ . DIRECTORY_SEPARATOR . $relative_path;
        }
        $stmt = $conn->prepare("DELETE FROM relief_distribution_reports WHERE report_id = ?");
        $stmt->bind_param("i", $report_id);
        if (!$stmt->execute()) {
            reliefRespond(['error' => 'Unable to delete receipt report'], 500);
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        reliefRespond(['ok' => true]);

    default:
        reliefRespond(['error' => 'Unknown action'], 400);
}
?>
