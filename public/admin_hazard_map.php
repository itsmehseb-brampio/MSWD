<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

$barangays = $conn->query("
    SELECT b.barangay_id, b.barangay_name, 
           bd.hazard_map, bd.hazard_description, bd.risk_level, 
           bd.evacuation_routes, bd.affected_areas, bd.boundaries,
           bd.map_lat, bd.map_lng, bd.map_zoom, bd.hazard_polygons, bd.hazard_points,
           bd.hazard_map_updated_at
    FROM barangays b 
    LEFT JOIN barangay_details bd ON b.barangay_id = bd.barangay_id 
    ORDER BY b.barangay_name
")->fetch_all(MYSQLI_ASSOC);

$totalBrgy = count($barangays);
$atRisk = 0;
$withMap = 0;
$totalFeatures = 0;
$riskCounts = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
$riskLevels = ['Critical' => [], 'High' => [], 'Medium' => [], 'Low' => []];
foreach ($barangays as $b) {
    if (in_array($b['risk_level'] ?? 'Low', ['High', 'Critical'])) $atRisk++;
    $hasLive = !empty($b['map_lat']) && !empty($b['map_lng']);
    $hasData = $hasLive || !empty($b['hazard_polygons']) || !empty($b['hazard_points']);
    if ($hasData) $withMap++;
    foreach (['hazard_polygons', 'hazard_points'] as $k) {
        $j = json_decode($b[$k] ?? '', true);
        if (is_array($j)) $totalFeatures += count($j);
    }
    $lvl = $b['risk_level'] ?? 'Low';
    if (!isset($riskCounts[$lvl])) $lvl = 'Low';
    $riskCounts[$lvl]++;
    $riskLevels[$lvl][] = $b['barangay_name'];
}

function featCount($b) {
    $c = 0;
    foreach (['hazard_polygons', 'hazard_points'] as $k) {
        $j = json_decode($b[$k] ?? '', true);
        if (is_array($j)) $c += count($j);
    }
    return $c;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hazard Map Information</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#eef2f6;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;display:flex;flex-direction:column;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:15px 28px;background:linear-gradient(135deg,#0072C6,#005999);color:white;box-shadow:0 2px 12px rgba(0,0,0,0.15);position:relative;z-index:1000;}
.header h1{font-size:1.25rem;display:flex;align-items:center;gap:10px;}
.header h1 i{font-size:1.1rem;}
.header-sub{font-size:0.78rem;color:rgba(255,255,255,0.75);margin-top:3px;}
.admin-profile{display:flex;align-items:center;gap:12px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.12);transition:background 0.3s;border:1px solid rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.22);}
.admin-profile img{width:38px;height:38px;border-radius:50%;border:2px solid white;object-fit:cover;}
.admin-profile span{font-size:0.88rem;font-weight:600;}
.dropdown{display:none;position:absolute;right:28px;top:68px;background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.18);overflow:hidden;z-index:1002;min-width:160px;border:1px solid #eee;}
.dropdown.show{display:block;animation:dropIn 0.2s ease;}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;font-size:0.85rem;}
.dropdown a:hover{background:#f0f5fb;color:#0072C6;}
.content{padding:22px 28px;flex:1;}

/* ============ STATS ============ */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:20px;}
.stat-card{background:white;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 3px 12px rgba(0,0,0,0.06);border-left:4px solid #0072C6;transition:transform 0.2s;}
.stat-card:hover{transform:translateY(-2px);}
.stat-card.warn{border-left-color:#dc3545;}
.stat-card.ok{border-left-color:#28a745;}
.stat-card.info{border-left-color:#17a2b8;}
.stat-card>i{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#0072C6;background:#e8f2fb;}
.stat-card.warn>i{color:#dc3545;background:#fdeaea;}
.stat-card.ok>i{color:#28a745;background:#e9f8ee;}
.stat-card.info>i{color:#17a2b8;background:#e6f6fa;}
.stat-card b{font-size:1.5rem;color:#222;display:block;line-height:1;}
.stat-card span{font-size:0.75rem;color:#777;font-weight:600;}

/* ============ TOOLBAR ============ */
.toolbar-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;}
.grid-title{font-size:1.1rem;color:#333;font-weight:700;display:flex;align-items:center;gap:8px;}
.grid-title i{color:#0072C6;}
.search-box{display:flex;align-items:center;gap:8px;background:white;border-radius:10px;padding:9px 14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e5e9ef;}
.search-box i{color:#999;font-size:0.85rem;}
.search-box input{border:none;outline:none;font-size:0.85rem;width:220px;font-family:'Segoe UI',sans-serif;color:#333;}
.search-box input::placeholder{color:#b0b6c0;}

/* ============ GRID ============ */
.hazard-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;}
.hazard-card{background:white;border-radius:14px;overflow:hidden;cursor:pointer;transition:all 0.25s;border:2px solid transparent;position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.06);}
.hazard-card:hover{transform:translateY(-4px);box-shadow:0 10px 24px rgba(0,0,0,0.12);border-color:#bcd9f2;}
.hazard-card.active{border-color:#0072C6;box-shadow:0 6px 18px rgba(0,114,198,0.2);}
.hazard-card .card-img{width:100%;height:150px;object-fit:cover;display:block;}
.hazard-card .card-img-placeholder{width:100%;height:150px;background:linear-gradient(135deg,#0a4a7e,#0072C6);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.85);flex-direction:column;gap:6px;}
.hazard-card .mini-map{background:#dfe5ec;pointer-events:none;}
.hazard-card .mini-map .leaflet-control-attribution{display:none;}
.hazard-card .mini-map .leaflet-marker-icon{display:none;}
.hazard-card .mini-map .leaflet-interactive{pointer-events:none;}
.hazard-card .card-img-placeholder i{font-size:1.6rem;}
.hazard-card .card-img-placeholder span{font-size:0.72rem;font-weight:600;}
.hazard-card .card-body{padding:13px 15px 15px;}
.hazard-card .card-top{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;}
.hazard-card .card-top h3{font-size:0.92rem;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hazard-card .card-meta{display:flex;justify-content:space-between;align-items:center;font-size:0.72rem;color:#8a97a8;border-top:1px solid #f0f2f5;padding-top:10px;}
.hazard-card .card-meta i{margin-right:4px;color:#0072C6;}
.risk-dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.risk-dot.low{background:#28a745;}
.risk-dot.medium{background:#fd7e14;}
.risk-dot.high{background:#dc3545;}
.risk-dot.critical{background:#7b1a1a;animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
.risk-badge{display:inline-flex;align-items:center;gap:5px;font-size:0.7rem;font-weight:700;color:white;padding:3px 10px;border-radius:20px;}
.risk-badge i{font-size:0.62rem;}
.risk-badge.risk-low{background:#28a745;}
.risk-badge.risk-medium{background:#fd7e14;}
.risk-badge.risk-high{background:#dc3545;}
.risk-badge.risk-critical{background:#7b1a1a;}

/* ============ RISK REPORT ============ */
.report-row{display:grid;grid-template-columns:1fr 1.25fr;gap:16px;margin-bottom:22px;}
.report-card{background:white;border-radius:14px;box-shadow:0 3px 12px rgba(0,0,0,0.06);overflow:hidden;}
.report-head{display:flex;align-items:center;gap:8px;padding:13px 18px;border-bottom:2px solid #f0f2f5;font-weight:700;font-size:0.9rem;color:#333;}
.report-head i{color:#0072C6;}
.chart-wrap{position:relative;height:235px;padding:16px 14px 6px;}
.risk-list{padding:14px 18px 16px;}
.risk-line{display:flex;align-items:center;gap:12px;margin-bottom:13px;}
.risk-bar{flex:1;height:11px;background:#eef1f4;border-radius:6px;overflow:hidden;}
.risk-bar-fill{height:100%;border-radius:6px;transition:width 0.6s ease;}
.risk-bar-fill.r-critical{background:#7b1a1a;}
.risk-bar-fill.r-high{background:#dc3545;}
.risk-bar-fill.r-medium{background:#fd7e14;}
.risk-bar-fill.r-low{background:#28a745;}
.risk-count{font-weight:700;font-size:0.92rem;color:#222;min-width:24px;text-align:right;}
.risk-names{font-size:0.76rem;color:#5a6673;margin:4px 0 11px;padding-left:2px;line-height:1.5;}
.risk-names-lbl{font-weight:700;margin-right:4px;}
.risk-names-lbl.risk-critical-txt{color:#7b1a1a;}
.risk-names-lbl.risk-high-txt{color:#dc3545;}
.risk-names-lbl.risk-medium-txt{color:#fd7e14;}
.risk-names-lbl.risk-low-txt{color:#28a745;}

/* ============ TAB BAR ============ */
.tab-bar{display:none;background:#e3e8ee;border-bottom:1px solid #d4dae1;min-height:42px;padding:0 14px;align-items:flex-end;overflow-x:auto;flex-shrink:0;}
.tab-bar.has-tabs{display:flex;}
.tab-bar::-webkit-scrollbar{height:4px;}
.tab-bar::-webkit-scrollbar-thumb{background:#b8c2cd;border-radius:4px;}
.tab-item{display:flex;align-items:center;gap:8px;padding:9px 15px;background:#d3dae2;border-radius:10px 10px 0 0;cursor:pointer;font-size:0.8rem;font-weight:600;color:#5a6673;white-space:nowrap;transition:all 0.2s;margin-right:3px;border:1px solid transparent;border-bottom:none;}
.tab-item:hover{background:#c6cfda;}
.tab-item.active{background:white;color:#0072C6;box-shadow:0 -2px 8px rgba(0,0,0,0.06);}
.tab-item .tab-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.tab-item .tab-x{display:flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;font-size:0.72rem;color:#9aa6b3;transition:all 0.2s;margin-left:2px;}
.tab-item .tab-x:hover{background:#dc3545;color:white;}

/* ============ PANEL ============ */
.detail-panel{display:none;background:white;border-radius:16px;box-shadow:0 6px 24px rgba(0,0,0,0.1);overflow:hidden;margin-bottom:26px;animation:panelSlide 0.3s ease;}
.detail-panel.active{display:block;}
@keyframes panelSlide{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.panel-topbar{display:flex;justify-content:space-between;align-items:center;padding:13px 22px;background:linear-gradient(135deg,#0072C6,#005999);color:white;}
.panel-topbar h3{font-size:1rem;display:flex;align-items:center;gap:8px;font-weight:700;}
.panel-topbar h3 i{font-size:0.9rem;}
.panel-actions{display:flex;gap:8px;align-items:center;}
.btn-panel{background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.25);padding:7px 16px;border-radius:8px;font-size:0.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;}
.btn-panel:hover{background:rgba(255,255,255,0.3);}

.panel-body{display:flex;min-height:480px;}
.panel-map{flex:1;position:relative;background:#e7ebf0;min-width:0;}
.live-map{position:absolute;inset:0;}
.map-pill{position:absolute;top:14px;right:14px;background:rgba(255,255,255,0.95);backdrop-filter:blur(4px);border-radius:20px;padding:7px 14px;font-size:0.72rem;color:#5a6673;box-shadow:0 2px 10px rgba(0,0,0,0.18);z-index:402;display:flex;align-items:center;gap:6px;}
.map-pill i{color:#0072C6;}
.hm-legend{position:absolute;bottom:14px;left:14px;background:rgba(255,255,255,0.95);backdrop-filter:blur(4px);border-radius:12px;padding:11px 14px;font-size:0.72rem;box-shadow:0 3px 14px rgba(0,0,0,0.18);z-index:402;max-width:220px;}
.hm-legend h5{font-size:0.68rem;color:#8a97a8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:7px;display:flex;align-items:center;gap:6px;}
.hm-legend h5 i{color:#0072C6;}
.hm-legend .lg-item{display:flex;align-items:center;gap:8px;margin:4px 0;color:#4a5562;font-weight:600;white-space:nowrap;}
.hm-legend .lg-poly{width:18px;height:12px;border-radius:2px;flex-shrink:0;}
.hm-legend .lg-line{width:22px;height:4px;border-radius:2px;flex-shrink:0;}
.hm-legend .lg-ico{width:16px;height:16px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;font-size:7px;color:#fff;flex-shrink:0;}
.panel-map .panel-map-img{width:100%;height:100%;object-fit:contain;background:#fbfcfe;display:block;}
.panel-map .no-map{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#8a97a8;text-align:center;padding:20px;}
.panel-map .no-map i{font-size:2.6rem;}
.panel-map .no-map p{font-size:0.85rem;}
.panel-info{width:380px;flex-shrink:0;padding:24px;overflow-y:auto;border-left:1px solid #e5e9ef;max-height:560px;background:#fbfcfe;}
.info-label-row{display:flex;align-items:center;gap:7px;font-size:0.7rem;color:#8a97a8;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;margin-bottom:5px;}
.info-label-row i{font-size:0.68rem;color:#0072C6;width:16px;text-align:center;}
.info-value{color:#333;font-size:0.86rem;line-height:1.45;margin-bottom:16px;padding:11px 13px;background:white;border-radius:10px;border-left:3px solid #0072C6;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.info-value.empty{color:#b0b6c0;font-style:italic;border-color:#d5dae0;}
.info-value.risk{display:flex;align-items:center;}
.info-value.boundary{border-color:#6f42c1;}
.info-value.desc{border-color:#0072C6;}
.info-value.route{border-color:#17a2b8;}
.info-value.areas{border-color:#e83e8c;}
.feat-list{max-height:280px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;margin-bottom:14px;padding-right:4px;}
.feat-item{display:flex;align-items:center;gap:10px;background:#f8f9fa;border-radius:10px;padding:8px 10px;border:1px solid #eef0f3;}
.feat-ico{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,.25);}
.feat-info{display:flex;flex-direction:column;line-height:1.25;min-width:0;}
.feat-info b{font-size:0.78rem;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.feat-info span{font-size:0.68rem;color:#8a97a8;font-weight:600;}
.feat-info small{font-size:0.66rem;color:#6a7682;font-family:Consolas,monospace;}
.feat-info small i{color:#0072C6;margin-right:2px;}
.feat-list::-webkit-scrollbar{width:4px;}
.feat-list::-webkit-scrollbar-thumb{background:#c9d2dc;border-radius:4px;}

.hm-divicon{background:transparent;border:none;}
.hm-ico{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;box-shadow:0 3px 8px rgba(0,0,0,0.4);border:2px solid #fff;transition:transform 0.2s;}
.hm-ico:hover{transform:scale(1.15);}

@media(max-width:1000px){.panel-body{flex-direction:column;}.panel-info{width:100%;border-left:none;border-top:1px solid #e5e9ef;max-height:none;}.stat-row{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));}.report-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <div>
                <h1><i class="fas fa-map-marked-alt"></i> Hazard Map Information</h1>
                <div class="header-sub">Review live hazard maps and risk information for all barangays</div>
            </div>
            <div style="position:relative;">
                <div class="admin-profile" onclick="toggleDropdown()">
                    <img src="mapa.png" alt="Admin">
                    <span><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar" id="tabBar"></div>

        <div class="content">
            <!-- Stats -->
            <div class="stat-row">
                <div class="stat-card"><i class="fas fa-building"></i><div><b><?php echo $totalBrgy; ?></b><span>Barangays</span></div></div>
                <div class="stat-card warn"><i class="fas fa-exclamation-triangle"></i><div><b><?php echo $atRisk; ?></b><span>High / Critical risk</span></div></div>
                <div class="stat-card ok"><i class="fas fa-map-marked-alt"></i><div><b><?php echo $withMap; ?></b><span>With saved maps</span></div></div>
                <div class="stat-card info"><i class="fas fa-draw-polygon"></i><div><b><?php echo $totalFeatures; ?></b><span>Hazard features</span></div></div>
            </div>

            <!-- Risk Report -->
            <div class="report-row">
                <div class="report-card">
                    <div class="report-head"><i class="fas fa-chart-pie"></i> Risk Level Distribution</div>
                    <div class="chart-wrap"><canvas id="riskChart"></canvas></div>
                </div>
                <div class="report-card">
                    <div class="report-head"><i class="fas fa-list-ul"></i> Barangay Risk Overview</div>
                    <div class="risk-list">
                        <?php foreach (['Critical', 'High', 'Medium', 'Low'] as $lvl): ?>
                        <div class="risk-line">
                            <span class="risk-badge risk-<?php echo strtolower($lvl); ?>"><?php echo $lvl; ?></span>
                            <div class="risk-bar"><div class="risk-bar-fill r-<?php echo strtolower($lvl); ?>" style="width:<?php echo $totalBrgy ? round($riskCounts[$lvl] / $totalBrgy * 100) : 0; ?>%;"></div></div>
                            <span class="risk-count"><?php echo $riskCounts[$lvl]; ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($totalBrgy === 0): ?>
                            <div class="risk-names"><span class="risk-names-lbl">No barangays found.</span></div>
                        <?php else: foreach (['Critical', 'High', 'Medium', 'Low'] as $lvl): if (count($riskLevels[$lvl])): ?>
                        <div class="risk-names">
                            <span class="risk-names-lbl risk-<?php echo strtolower($lvl); ?>-txt"><?php echo $lvl; ?>:</span>
                            <?php echo htmlspecialchars(implode(', ', $riskLevels[$lvl])); ?>
                        </div>
                        <?php endif; endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel -->
            <div id="panelContainer"></div>

            <!-- Grid -->
            <div class="grid-section" id="gridSection">
                <div class="toolbar-row">
                    <div class="grid-title"><i class="fas fa-layer-group"></i> All Barangay Hazard Maps</div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search barangay..." autocomplete="off">
                    </div>
                </div>
                <div class="hazard-grid">
                    <?php foreach ($barangays as $b): ?>
                    <div class="hazard-card" id="card-<?php echo $b['barangay_id']; ?>" data-name="<?php echo htmlspecialchars($b['barangay_name'], ENT_QUOTES); ?>" onclick="clickCard(<?php echo $b['barangay_id']; ?>)">
                        <?php if (!empty($b['map_lat']) && !empty($b['map_lng'])): ?>
                            <div class="card-img-placeholder mini-map" id="mmap-<?php echo $b['barangay_id']; ?>"></div>
                        <?php else: ?>
                            <div class="card-img-placeholder"><i class="fas fa-map-marked-alt"></i><span>No map saved</span></div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="card-top">
                                <h3><?php echo htmlspecialchars($b['barangay_name']); ?></h3>
                                <span class="risk-badge risk-<?php echo strtolower($b['risk_level'] ?? 'Low'); ?>"><i class="fas fa-circle" style="font-size:0.5rem;"></i> <?php echo htmlspecialchars($b['risk_level'] ?? 'Low'); ?></span>
                            </div>
                            <div class="card-meta">
                                <span><i class="fas fa-draw-polygon"></i><?php echo featCount($b); ?> feature<?php echo featCount($b) === 1 ? '' : 's'; ?></span>
                                <span><?php echo !empty($b['hazard_map_updated_at']) ? '<i class="fas fa-clock"></i>' . htmlspecialchars(date('M j', strtotime($b['hazard_map_updated_at']))) : '<i class="fas fa-clock"></i>Not saved'; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const barangays = <?php echo json_encode($barangays); ?>;
const riskCounts = <?php echo json_encode($riskCounts); ?>;
const openTabs = {};
let activeTabId = null;

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.admin-profile'))document.getElementById("dropdownMenu").classList.remove("show");};

document.getElementById('searchInput').addEventListener('input', function(){
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.hazard-card').forEach(c => {
        c.style.display = (c.dataset.name || '').toLowerCase().includes(q) ? '' : 'none';
    });
});

function clickCard(ref) {
    const data = (typeof ref === 'object') ? ref : barangays.find(b => String(b.barangay_id) === String(ref));
    if (!data) return;
    const id = data.barangay_id;
    if (!openTabs[id]) {
        openTabs[id] = {data: data};
        activeTabId = id;
        renderTabBar();
        renderPanel(data);
        highlightCard(id);
    } else {
        activeTabId = id;
        showPanel(id);
        highlightCard(id);
        renderTabBar();
    }
}

function closeTab(id, e) {
    e.stopPropagation();
    delete openTabs[id];
    document.getElementById('panel-' + id)?.remove();
    document.getElementById('card-' + id)?.classList.remove('active');
    renderTabBar();
    if (String(activeTabId) === String(id)) {
        const keys = Object.keys(openTabs);
        if (keys.length > 0) {
            const lastId = parseInt(keys[keys.length - 1]);
            activeTabId = lastId;
            showPanel(lastId);
            highlightCard(lastId);
            renderTabBar();
        } else {
            activeTabId = null;
        }
    }
}

function switchTab(id) {
    activeTabId = id;
    showPanel(id);
    highlightCard(id);
    renderTabBar();
}

function showPanel(id) {
    document.querySelectorAll('.detail-panel').forEach(p => p.classList.remove('active'));
    const p = document.getElementById('panel-' + id);
    if (p) p.classList.add('active');
}

function highlightCard(id) {
    document.querySelectorAll('.hazard-card').forEach(c => c.classList.remove('active'));
    const card = document.getElementById('card-' + id);
    if (card) card.classList.add('active');
}

function renderTabBar() {
    const bar = document.getElementById('tabBar');
    const ids = Object.keys(openTabs);
    if (ids.length === 0) { bar.classList.remove('has-tabs'); bar.innerHTML = ''; return; }
    bar.classList.add('has-tabs');
    let html = '';
    ids.forEach(id => {
        const d = openTabs[id].data;
        const riskColor = getRiskColor(d.risk_level);
        const isActive = String(id) === String(activeTabId) ? 'active' : '';
        html += '<div class="tab-item ' + isActive + '" onclick="switchTab(' + id + ')">';
        html += '<span class="tab-dot" style="background:' + riskColor + ';"></span>';
        html += '<span>' + esc(d.barangay_name) + '</span>';
        html += '<span class="tab-x" onclick="closeTab(' + id + ', event)">&times;</span>';
        html += '</div>';
    });
    bar.innerHTML = html;
}

const LEGEND_HTML =
    '<div class="hm-legend">' +
    '<h5><i class="fas fa-list-ul"></i> Legend</h5>' +
    '<div class="lg-item"><span class="lg-poly" style="background:#0072C6;"></span> Barangay Boundary</div>' +
    '<div class="lg-item"><span class="lg-line" style="background:#dc3545;"></span> Prone Area</div>' +
    '<div class="lg-item"><span class="lg-ico" style="background:#0072C6;"><i class="fas fa-hospital"></i></span> Evacuation Center</div>' +
    '<div class="lg-item"><span class="lg-ico" style="background:#e8710a;"><i class="fas fa-university"></i></span> Barangay Hall</div>' +
    '<div class="lg-item"><span class="lg-ico" style="background:#28a745;"><i class="fas fa-school"></i></span> School</div>' +
    '</div>';

function renderPanel(data) {
    const id = data.barangay_id;
    const container = document.getElementById('panelContainer');

    document.getElementById('panel-' + id)?.remove();

    const riskClass = 'risk-' + (data.risk_level || 'Low').toLowerCase();
    const riskColor = getRiskColor(data.risk_level);

    let html = '<div class="detail-panel active" id="panel-' + id + '">';

    html += '<div class="panel-topbar">';
    html += '<h3><i class="fas fa-map-marked-alt"></i> ' + esc(data.barangay_name) + '</h3>';
    html += '<div class="panel-actions"><span class="risk-badge ' + riskClass + '"><i class="fas fa-circle" style="font-size:0.62rem;"></i> ' + esc(data.risk_level || 'Low') + '</span></div>';
    html += '</div>';

    html += '<div class="panel-body">';

    html += '<div class="panel-map" id="pmap-' + id + '">';
    if (data.map_lat && data.map_lng) {
        html += '<div id="hmap-' + id + '" class="live-map"></div>';
        html += '<div class="map-pill"><i class="fas fa-clock"></i> Last saved: ' + (data.hazard_map_updated_at ? esc(fmtAdminTs(data.hazard_map_updated_at)) : 'Not yet') + '</div>';
        html += LEGEND_HTML;
    } else {
        html += '<div class="no-map"><i class="fas fa-map"></i><p>No map saved yet</p></div>';
    }
    html += '</div>';

    html += '<div class="panel-info">';

    html += '<div class="info-label-row"><i class="fas fa-clock"></i> Last Updated</div>';
    html += '<div class="info-value' + (!data.hazard_map_updated_at ? ' empty' : '') + '" style="border-color:#17a2b8;">' + (data.hazard_map_updated_at ? esc(fmtAdminTs(data.hazard_map_updated_at)) : 'Not yet saved') + '</div>';
    html += '<div class="info-label-row"><i class="fas fa-exclamation-triangle"></i> Risk Level</div>';
    html += '<div class="info-value risk"><span class="risk-badge ' + riskClass + '"><i class="fas fa-circle" style="font-size:0.5rem;"></i> ' + esc(data.risk_level || 'Low') + '</span></div>';
    html += featureListHTML(data);

    html += '</div></div></div>';

    container.insertAdjacentHTML('beforeend', html);

    if (data.map_lat && data.map_lng) {
        setTimeout(function(){ initHazardView(id, data); }, 60);
    }
}

const VIEW_META = {
    boundary: { label: 'Barangay Boundary', icon: 'fa-map-marker-alt', color: '#0072C6' },
    prone:    { label: 'Prone Area', icon: 'fa-triangle-exclamation', color: '#dc3545' },
    evac:     { label: 'Evacuation Center', icon: 'fa-hospital', color: '#0072C6' },
    hall:     { label: 'Barangay Hall', icon: 'fa-university', color: '#e8710a' },
    school:   { label: 'School', icon: 'fa-school', color: '#28a745' }
};

function featureListHTML(data){
    let items = [];
    let poly = [], pts = [];
    try { poly = data.hazard_polygons ? JSON.parse(data.hazard_polygons) : []; } catch(e){ poly = []; }
    try { pts = data.hazard_points ? JSON.parse(data.hazard_points) : []; } catch(e){ pts = []; }
    poly.forEach(p => {
        if (!p.points || !p.points.length) return;
        const meta = VIEW_META[p.kind] || VIEW_META.prone;
        const mid = p.points[Math.floor(p.points.length / 2)] || p.points[0];
        items.push({
            icon: (p.kind === 'prone' ? 'fa-triangle-exclamation' : 'fa-map-marker-alt'),
            color: p.color || meta.color,
            name: p.name || meta.label,
            sub: p.kind === 'prone' ? 'Prone Area' + (p.ptype ? ': ' + p.ptype : '') : meta.label,
            lat: Number(mid[0]), lng: Number(mid[1])
        });
    });
    pts.forEach(m => {
        const meta = VIEW_META[m.kind] || VIEW_META.evac;
        items.push({ icon: meta.icon, color: m.color || meta.color, name: m.name || meta.label, sub: meta.label, lat: parseFloat(m.lat), lng: parseFloat(m.lng) });
    });
    if (!items.length) {
        return '<div class="info-label-row"><i class="fas fa-draw-polygon"></i> Map Features</div><div class="info-value empty">No features marked yet</div>';
    }
    let html = '<div class="info-label-row"><i class="fas fa-draw-polygon"></i> Map Features (' + items.length + ')</div>';
    html += '<div class="feat-list">';
    items.forEach(it => {
        html += '<div class="feat-item">';
        html += '<span class="feat-ico" style="background:' + it.color + ';"><i class="fas ' + it.icon + '"></i></span>';
        html += '<div class="feat-info"><b>' + esc(it.name) + '</b><span>' + esc(it.sub) + '</span>';
        html += '<small><i class="fas fa-map-pin"></i> ' + it.lat.toFixed(5) + ', ' + it.lng.toFixed(5) + '</small></div>';
        html += '</div>';
    });
    html += '</div>';
    return html;
}

function viewMarkerIcon(color, icon){
    return L.divIcon({
        className: 'hm-divicon',
        html: '<div class="hm-ico" style="background:' + color + '"><i class="fas ' + icon + '"></i></div>',
        iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -18]
    });
}

function drawFeatures(map, data){
    let items = [];
    let jsonPoly = [];
    try { jsonPoly = data.hazard_polygons ? JSON.parse(data.hazard_polygons) : []; } catch(e) { jsonPoly = []; }
    jsonPoly.forEach(p => {
        if (!p.points || !p.points.length) return;
        const meta = VIEW_META[p.kind] || VIEW_META.prone;
        const layer = (p.type === 'line')
            ? L.polyline(p.points, { color: p.color || meta.color, weight: 6, opacity: 0.95 })
            : L.polygon(p.points, { color: p.color || meta.color, fillColor: p.color || meta.color, fillOpacity: 0.25, weight: 3 });
        layer.addTo(map);
        const mid = p.points[Math.floor(p.points.length / 2)] || p.points[0];
        const sub = p.kind === 'prone' ? 'Prone Area' + (p.ptype ? ': ' + p.ptype : '') : meta.label;
        layer.bindPopup('<b>' + esc(p.name || meta.label) + '</b><br><small>' + sub + '</small><br><small style="font-family:Consolas,monospace;color:#555;"><i class="fas fa-map-pin"></i> ' + Number(mid[0]).toFixed(5) + ', ' + Number(mid[1]).toFixed(5) + '</small>');
        items.push(layer);
    });

    let jsonPts = [];
    try { jsonPts = data.hazard_points ? JSON.parse(data.hazard_points) : []; } catch(e) { jsonPts = []; }
    jsonPts.forEach(m => {
        const meta = VIEW_META[m.kind] || VIEW_META.evac;
        const lat = parseFloat(m.lat), lng = parseFloat(m.lng);
        const layer = L.marker([lat, lng], { icon: viewMarkerIcon(m.color || meta.color, meta.icon) });
        layer.addTo(map);
        layer.bindPopup('<b>' + esc(m.name || meta.label) + '</b><br><small>' + meta.label + '</small><br><small style="font-family:Consolas,monospace;color:#555;"><i class="fas fa-map-pin"></i> ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</small>');
        items.push(layer);
    });
    return items;
}

function initHazardView(id, data){
    const el = document.getElementById('hmap-' + id);
    if (!el || el._hmMap) return;

    const map = L.map(el, { zoomControl: true }).setView([parseFloat(data.map_lat), parseFloat(data.map_lng)], parseInt(data.map_zoom || 14));
    el._hmMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20, attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const features = drawFeatures(map, data);
    if (features.length) {
        const grp = L.featureGroup(features);
        map.fitBounds(grp.getBounds(), { padding: [40, 40] });
    } else {
        map.invalidateSize();
    }
}

function initMiniMap(id, data){
    const el = document.getElementById('mmap-' + id);
    if (!el || el._hmMap) return;

    const map = L.map(el, {
        zoomControl: false, scrollWheelZoom: false, dragging: false,
        touchZoom: false, doubleClickZoom: false, boxZoom: false,
        keyboard: false, attributionControl: false, interactive: false
    }).setView([parseFloat(data.map_lat), parseFloat(data.map_lng)], parseInt(data.map_zoom || 14));
    el._hmMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20 }).addTo(map);

    const features = drawFeatures(map, data);
    if (features.length) {
        map.fitBounds(L.featureGroup(features).getBounds(), { padding: [16, 16] });
    }
}

function getRiskColor(l) {
    return {'Low':'#28a745','Medium':'#fd7e14','High':'#dc3545','Critical':'#7b1a1a'}[l]||'#28a745';
}

(function initRiskChart(){
    const el = document.getElementById('riskChart');
    if (!el || typeof Chart === 'undefined') return;
    const labels = ['Critical','High','Medium','Low'];
    const colors = labels.map(l => getRiskColor(l));
    const data = labels.map(l => riskCounts[l] || 0);
    const total = data.reduce((a,b)=>a+b,0) || 1;
    const shown = labels.filter((l,i)=>data[i]>0);
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: shown,
            datasets: [{ data: data.filter(d=>d>0), backgroundColor: colors.filter((c,i)=>data[i]>0), borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11, weight: '600' }, color: '#4a5562' } },
                tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.parsed + ' (' + Math.round(c.parsed / total * 100) + '%)' } }
            }
        }
    });
})();
document.querySelectorAll('.mini-map').forEach(function(el){
    const id = parseInt(el.id.replace('mmap-', ''), 10);
    const data = barangays.find(b => String(b.barangay_id) === String(id));
    if (data) initMiniMap(id, data);
});
function fmtAdminTs(s){
    const d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d)) return s;
    return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}
function esc(t){if(!t)return '';const d=document.createElement('div');d.appendChild(document.createTextNode(t));return d.innerHTML;}
</script>
</body>
</html>
