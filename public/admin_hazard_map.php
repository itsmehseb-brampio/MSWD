<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/municipal_hazard_map_data.php';

$mapData = loadMunicipalHazardMapData($conn);
$barangayResult = $conn->query(
    "SELECT COUNT(DISTINCT barangays.barangay_id) AS total_barangays,
            COUNT(DISTINCT CASE
                WHEN barangay_details.risk_level IN ('High', 'Critical') THEN barangays.barangay_id
            END) AS high_or_critical
     FROM barangays
     LEFT JOIN barangay_details ON barangay_details.barangay_id = barangays.barangay_id"
);
if (!$barangayResult) {
    throw new RuntimeException('Unable to load barangay hazard summary: ' . $conn->error);
}
$barangayStats = $barangayResult->fetch_assoc();
$adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Malilipot Municipality Hazard Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #EFF6FF; color: #1E3A8A; font-family: "Segoe UI", sans-serif; }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 18px 24px; background: #1E40AF; color: #fff; }
        .page-header h1 { margin: 0; font-size: 1.15rem; }
        .page-header nav { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .page-header a { color: #fff; text-decoration: none; font-size: .9rem; }
        .page-content { max-width: 1500px; margin: 0 auto; padding: 24px; }
        .admin-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .summary-card { padding: 18px 20px; background: #fff; border: 1px solid #BFDBFE; border-radius: 12px; }
        .summary-card span { display: block; color: #475569; font-size: .88rem; }
        .summary-card strong { display: block; margin-top: 6px; color: #1E3A8A; font-size: 1.5rem; }
        @media (max-width: 600px) {
            .page-header { align-items: flex-start; flex-direction: column; }
            .page-content { padding: 14px; }
            .admin-summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="page-header">
        <h1><i class="fas fa-map-marked-alt" aria-hidden="true"></i> Malilipot Municipality Hazard Map</h1>
        <nav aria-label="Admin navigation">
            <span><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <main class="page-content">
        <section class="admin-summary" aria-label="Barangay hazard summary">
            <div class="summary-card">
                <span>Total barangays</span>
                <strong><?= number_format((int) ($barangayStats['total_barangays'] ?? 0)) ?></strong>
            </div>
            <div class="summary-card">
                <span>High or critical risk</span>
                <strong><?= number_format((int) ($barangayStats['high_or_critical'] ?? 0)) ?></strong>
            </div>
        </section>
        <?php require __DIR__ . '/municipal_hazard_map_canvas.php'; ?>
    </main>
</body>
</html>
