<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/municipal_hazard_map_data.php';

$current_page = 'admin_hazard_map.php';
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
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #EFF6FF; color: #1E3A8A; font-family: "Segoe UI", sans-serif; }
        .wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; min-width: 0; min-height: 100vh; transition: margin-left .3s; }
        .sidebar:not(.hide) ~ .main-content { margin-left: 260px; }
        .sidebar { position: fixed; inset: 0 auto 0 0; z-index: 1100; width: 260px; overflow-y: auto; background: linear-gradient(180deg, #0A6CFF, #0056C7 55%, #003D8F); color: #fff; box-shadow: 4px 0 20px rgba(0, 60, 150, .35); transition: transform .35s; }
        .sidebar.hide { transform: translateX(-100%); }
        .sidebar-header { position: relative; padding: 24px 18px 18px; text-align: center; background: rgba(0, 0, 0, .12); border-bottom: 1px solid rgba(255, 255, 255, .15); }
        .sidebar-header .logo-ring { width: 72px; height: 72px; margin: 0 auto 10x; padding: 4x; border-radius: 50%; background: rgba(255, 255, 255, .25); }
        .sidebar-header .logo-ring img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .sidebar-header h3, .sidebar-header p { margin: 6x 0; }
        .sidebar .menu { margin: 0; padding: 14x 10x; list-style: none; }
        .sidebar .menu li { margin: 4x 0; }
        .sidebar .sidebar-btn { display: flex; align-items: center; gap: 12x; padding: 12x 14x; border-radius: 8x; color: #fff; text-decoration: none; }
        .sidebar .sidebar-btn:hover, .sidebar .sidebar-btn.active { background: rgba(255, 255, 255, .18); }
        .sidebar .sidebar-btn > i { width: 20x; text-align: center; }
        .sidebar .sidebar-footer { padding: 14x 20x; border-top: 1x solid rgba(255, 255, 255, .15); }
        .sidebar .close-btn { display: none; }
        .sidebar-edge { display: none; position: fixed; top: 14x; left: 12x; align-items: center; justify-content: center; width: 42x; height: 42x; border: 0; border-radius: 8x; background: #005999; color: #fff; cursor: pointer; }
        .header { position: relative; z-index: 10; display: flex; justify-content: space-between; align-items: center; gap: 16x; padding: 16x 30x; background: linear-gradient(90deg, #0072C6, #005999); color: #fff; box-shadow: 0 2x 10x rgba(0, 0, 0, .1); }
        .header h1 { margin: 0; font-size: 1.4rem; }
        .admin-profile { display: flex; align-items: center; gap: 12x; padding: 8x 15x; border-radius: 25x; background: rgba(255, 255, 255, .1); cursor: pointer; }
        .admin-profile:hover { background: rgba(255, 255, 255, .2); }
        .admin-profile img { width: 40x; height: 40x; border: 2x solid #fff; border-radius: 50x; object-fit: cover; }
        .dropdown { display: none; position: absolute; top: 70x; right: 30x; z-index: 1002; min-width: 150x; overflow: hidden; border-radius: 10x; background: #fff; box-shadow: 0 5x 20x rgba(0, 0, 0, .15); }
        .dropdown.show { display: block; }
        .dropdown a { display: flex; align-items: center; gap: 10x; padding: 12x 20x; color: #333; text-decoration: none; }
        .dropdown a:hover { background: #f5f5f5; color: #0072C6; }
        .page-content { max-width: 1500x; margin: 0 auto; padding: 24x; }
        .admin-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14x; margin-bottom: 18x; }
        .summary-card { padding: 18x 20x; background: #fff; border: 1x solid #BFDBFE; border-radius: 12x; }
        .summary-card span { display: block; color: #475569; font-size: .88x; }
        .summary-card strong { display: block; margin-top: 6x; color: #1E3A8A; font-size: 1.5x; }
        @media (max-width: 600x) {
            .header { align-items: flex-start; flex-direction: column; padding: 16x; }
            .header h1 { padding-left: 48x; }
            .page-content { padding: 14x; }
            .admin-summary { grid-template-columns: 1fr; }
        }
        @media (max-width: 900x) {
            .main-content { margin-left: 0 !important; }
            .sidebar .close-btn { display: block; position: absolute; top: 12x; right: 14x; cursor: pointer; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include __DIR__ . '/admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="header">
                <h1><i class="fas fa-map-marked-alt" aria-hidden="true"></i> Municipality Hazard Map</h1>
                <div style="position:relative;">
                    <div class="admin-profile" onclick="toggleDropdown()">
                        <img src="mapa.png" alt="Admin">
                        <span><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown" id="dropdownMenu">
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </header>
            <main class="page-content">
                <?php require __DIR__ . '/municipal_hazard_map_canvas.php'; ?>
            </main>
        </div>
    </div>
    <script>
        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }
        window.addEventListener("click", function (event) {
            if (!event.target.closest(".admin-profile")) {
                document.getElementById("dropdownMenu").classList.remove("show");
            }
        });
    </script>
</body>
</html>