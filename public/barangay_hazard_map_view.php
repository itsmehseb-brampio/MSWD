<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header('Location: barangay_login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/municipal_hazard_map_data.php';

$mapData = loadMunicipalHazardMapData($conn);
$barangayName = $_SESSION['barangay_name'] ?? 'Barangay';
$current_page = basename($_SERVER['PHP_SELF']);
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
        .wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; min-width: 0; }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 18px 24px; background: #1E40AF; color: #fff; }
        .page-header h1 { margin: 0; font-size: 1.15rem; }
        .page-header span, .page-header a { color: #fff; font-size: .9rem; }
        .page-header nav { display: flex; gap: 16px; flex-wrap: wrap; }
        .page-header a { text-decoration: none; }
        .page-content { max-width: 1500px; margin: 0 auto; padding: 24px; }
        @media (max-width: 600px) { .page-header { align-items: flex-start; flex-direction: column; } .page-content { padding: 14px; } }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include __DIR__ . '/barangay_sidebar.php'; ?>
    <div class="main-content">
        <header class="page-header">
            <h1><i class="fas fa-map-marked-alt"></i> Malilipot Municipality Hazard Map</h1>
            <nav aria-label="Barangay navigation">
                <span><?= htmlspecialchars($barangayName, ENT_QUOTES, 'UTF-8') ?></span>
                <a href="barangay_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main class="page-content">
            <?php require __DIR__ . '/municipal_hazard_map_canvas.php'; ?>
        </main>
    </div>
</div>
</body>
</html>
