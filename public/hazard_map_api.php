<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['barangay_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$barangay_id = intval($_SESSION['barangay_id']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save') {
    $lat = isset($_POST['map_lat']) && $_POST['map_lat'] !== '' ? floatval($_POST['map_lat']) : null;
    $lng = isset($_POST['map_lng']) && $_POST['map_lng'] !== '' ? floatval($_POST['map_lng']) : null;
    $zoom = isset($_POST['map_zoom']) && $_POST['map_zoom'] !== '' ? intval($_POST['map_zoom']) : 14;
    $polygons = $_POST['hazard_polygons'] ?? '[]';
    $points = $_POST['hazard_points'] ?? '[]';

    if ($lat !== null && ($lat < -90 || $lat > 90)) { $lat = null; }
    if ($lng !== null && ($lng < -180 || $lng > 180)) { $lng = null; }
    if ($zoom < 1 || $zoom > 20) { $zoom = 14; }

    json_decode($polygons);
    json_decode($points);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid map data']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE barangay_details SET map_lat=?, map_lng=?, map_zoom=?, hazard_polygons=?, hazard_points=?, hazard_map_updated_at=NOW() WHERE barangay_id=?");
    $stmt->bind_param("ddissi", $lat, $lng, $zoom, $polygons, $points, $barangay_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'updated_at' => date('Y-m-d H:i:s')]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
    exit;
}

if ($action === 'set_risk') {
    $risk = $_POST['risk_level'] ?? '';
    if (!in_array($risk, ['Low', 'Medium', 'High', 'Critical'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid risk level']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE barangay_details SET risk_level=? WHERE barangay_id=?");
    $stmt->bind_param("si", $risk, $barangay_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'risk_level' => $risk]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
