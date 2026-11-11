<?php
session_start();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
    exit;
}

$role_stmt = $conn->prepare(
    "SELECT 1 FROM model_has_roles mr
     JOIN roles r ON r.id = mr.role_id
     WHERE mr.model_type = ? AND mr.model_id = ? AND r.name = 'admin'
     LIMIT 1"
);
if (!$role_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to verify administrator permissions']);
    exit;
}
$admin_model = 'App\\Models\\Admin';
$admin_id = (int) $_SESSION['admin_id'];
$role_stmt->bind_param('si', $admin_model, $admin_id);
if (!$role_stmt->execute()) {
    $role_stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to verify administrator permissions']);
    exit;
}
$role_result = $role_stmt->get_result();
$is_admin = $role_result && $role_result->num_rows > 0;
$role_stmt->close();
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only administrators can edit the shared hazard map']);
    exit;
}

if (!isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !is_string($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request token']);
    exit;
}

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

    $decoded_polygons = json_decode($polygons, true);
    $polygons_valid = json_last_error() === JSON_ERROR_NONE && is_array($decoded_polygons) && array_is_list($decoded_polygons);
    $decoded_points = json_decode($points, true);
    $points_valid = json_last_error() === JSON_ERROR_NONE && is_array($decoded_points) && array_is_list($decoded_points);
    if (!$polygons_valid || !$points_valid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid map data']);
        exit;
    }

    $map_id = 1;
    $stmt = $conn->prepare("INSERT INTO municipal_hazard_maps (id, map_lat, map_lng, map_zoom, hazard_polygons, hazard_points, hazard_map_updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE map_lat=VALUES(map_lat), map_lng=VALUES(map_lng), map_zoom=VALUES(map_zoom), hazard_polygons=VALUES(hazard_polygons), hazard_points=VALUES(hazard_points), hazard_map_updated_at=NOW()");
    $stmt->bind_param("iddiss", $map_id, $lat, $lng, $zoom, $polygons, $points);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'updated_at' => date('Y-m-d H:i:s')]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
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
    $map_id = 1;
    $stmt = $conn->prepare("INSERT INTO municipal_hazard_maps (id, risk_level, hazard_map_updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE risk_level=VALUES(risk_level), hazard_map_updated_at=NOW()");
    $stmt->bind_param("is", $map_id, $risk);
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
