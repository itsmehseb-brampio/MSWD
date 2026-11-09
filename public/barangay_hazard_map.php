<?php
// Session must start before any output
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
// Rest of the file initialization
require 'db.php';

$barangay_id = intval($_SESSION['barangay_id']);
$barangay_name = $_SESSION['barangay_name'] ?? '';

$detail = $conn->query("SELECT map_lat, map_lng, map_zoom, hazard_polygons, hazard_points, risk_level, hazard_map_updated_at FROM barangay_details WHERE barangay_id = $barangay_id")->fetch_assoc();

$mapData = [
    'lat' => null, 'lng' => null, 'zoom' => 14,
    'polygons' => [], 'points' => [], 'risk_level' => 'Low'
];
if ($detail) {
    $mapData['risk_level'] = $detail['risk_level'] ?? 'Low';
    if ($detail['map_lat'] !== null) {
        $mapData['lat'] = floatval($detail['map_lat']);
        $mapData['lng'] = floatval($detail['map_lng']);
        $mapData['zoom'] = intval($detail['map_zoom'] ?: 14);
        $mapData['polygons'] = json_decode($detail['hazard_polygons'] ?? '[]', true) ?: [];
        $mapData['points'] = json_decode($detail['hazard_points'] ?? '[]', true) ?: [];
        $mapData['updated_at'] = !empty($detail['hazard_map_updated_at']) ? $detail['hazard_map_updated_at'] : null;
    }
}