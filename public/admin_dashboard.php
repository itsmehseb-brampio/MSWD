<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

$hasReportTable = @in_array('disaster_reports', array_map(function($t){ return $t[0]; }, $conn->query("SHOW TABLES")->fetch_all()));

$barangays = $conn->query("SELECT b.barangay_id, b.barangay_name, b.address, COALESCE(bd.households,0) as households FROM barangays b LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id ORDER BY b.barangay_name");
$barangayList = [];
$riskCounts = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
$riskBarangays = ['Low' => [], 'Medium' => [], 'High' => [], 'Critical' => []];
$totalReportsAll = 0;
if ($barangays) {
    while ($b = $barangays->fetch_assoc()) {
        $bid = $b['barangay_id'];

        $reportCount = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $declinedCount = 0;
        if ($hasReportTable) {
            $r = $conn->query("SELECT COUNT(*) as cnt FROM disaster_reports WHERE barangay_id=$bid");
            if ($r) $reportCount = $r->fetch_assoc()['cnt'];
            $r = $conn->query("SELECT COUNT(*) as cnt FROM disaster_reports WHERE barangay_id=$bid AND status='pending'");
            if ($r) $pendingCount = $r->fetch_assoc()['cnt'];
            $r = $conn->query("SELECT COUNT(*) as cnt FROM disaster_reports WHERE barangay_id=$bid AND status='approved'");
            if ($r) $approvedCount = $r->fetch_assoc()['cnt'];
            $r = $conn->query("SELECT COUNT(*) as cnt FROM disaster_reports WHERE barangay_id=$bid AND status='declined'");
            if ($r) $declinedCount = $r->fetch_assoc()['cnt'];
        }

        $contactCount = 0;
            $r = $conn->query("SELECT COUNT(*) as cnt FROM barangay_contacts WHERE barangay_id=$bid");
        if ($r) $contactCount = $r->fetch_assoc()['cnt'];

        $totalReportsAll += $reportCount;

        $detail = $conn->query("SELECT * FROM barangay_details WHERE barangay_id=$bid")->fetch_assoc();

        $lvl = $detail['risk_level'] ?? 'Low';
        if (!isset($riskCounts[$lvl])) $lvl = 'Low';
        $riskCounts[$lvl]++;
        $riskBarangays[$lvl][] = $b['barangay_name'];

        $barangayList[] = [
            'id' => $bid,
            'name' => $b['barangay_name'],
            'address' => $b['address'] ?? '',
            'households' => intval($b['households']),
            'reports' => $reportCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'declined' => $declinedCount,
            'contacts' => $contactCount,
            'detail' => $detail
        ];
    }
}

$totalBarangays = count($barangayList);
$totalHouseholdsAll = 0;
foreach ($barangayList as $bb) $totalHouseholdsAll += intval($bb['households']);
$highCriticalCount = $riskCounts['High'] + $riskCounts['Critical'];

$announcements = $conn->query("SELECT announcement_id, title, message, is_pinned, created_at FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Chart data
$chartBarangays = []; $chartPopulation = []; $chartHouseholds = []; $chartHead = []; $chartReports = [];
foreach ($barangayList as $b) {
    $chartBarangays[] = $b['name'];
    $chartPopulation[] = intval($b['detail']['population'] ?? 0);
    $chartHouseholds[] = intval($b['detail']['households'] ?? 0);
    $chartHead[] = intval($b['detail']['head_of_household'] ?? 0);
    $chartReports[] = $b['reports'];
}
$statusCounts = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
$damageTotally = 0; $damagePartially = 0;
if ($hasReportTable) {
    $r = $conn->query("SELECT status, COUNT(*) AS c FROM disaster_reports GROUP BY status");
    if ($r) while ($x = $r->fetch_assoc()) if (isset($statusCounts[$x['status']])) $statusCounts[$x['status']] = intval($x['c']);
    $r = $conn->query("SELECT SUM(damage_extent='Totally') AS t, SUM(damage_extent='Partially') AS p FROM disaster_reports");
    if ($r) { $dam = $r->fetch_assoc(); $damageTotally = intval($dam['t']); $damagePartially = intval($dam['p']); }
}
$chart_json = [
    'barangays' => $chartBarangays,
    'population' => $chartPopulation,
    'households' => $chartHouseholds,
    'head' => $chartHead,
    'reports' => $chartReports,
    'status' => $statusCounts,
    'damage' => ['Totally' => $damageTotally, 'Partially' => $damagePartially],
    'risk' => $riskCounts,
    'status_by_brgy' => []
];
if ($hasReportTable) {
    $sbb = ['labels' => $chartBarangays, 'pending' => [], 'approved' => [], 'declined' => [], 'cancelled' => [], 'reedit' => []];
    $map = [];
    $r = $conn->query("SELECT b.barangay_name AS n, dr.status AS s, COUNT(*) AS c FROM disaster_reports dr LEFT JOIN barangays b ON dr.barangay_id=b.barangay_id GROUP BY dr.barangay_id, dr.status");
    if ($r) while ($x = $r->fetch_assoc()) { $n = $x['n'] ?: 'Unknown'; if (!isset($map[$n])) $map[$n] = []; if (isset($sbb[$x['s']])) $map[$n][$x['s']] = intval($x['c']); }
    foreach (['pending', 'approved', 'declined', 'cancelled', 'reedit'] as $s) {
        $out = [];
        foreach ($chartBarangays as $n) $out[] = $map[$n][$s] ?? 0;
        $sbb[$s] = $out;
    }
    $chart_json['status_by_brgy'] = $sbb;
}

// Per-barangay chart data (for the barangay detail tabs)
$per = [];
if ($hasReportTable) {
    foreach ($barangayList as $b) {
        $bid = $b['id'];
        $bStatus = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
        $r = $conn->query("SELECT status, COUNT(*) AS c FROM disaster_reports WHERE barangay_id=$bid GROUP BY status");
        if ($r) while ($x = $r->fetch_assoc()) if (isset($bStatus[$x['status']])) $bStatus[$x['status']] = intval($x['c']);
        $bMonths = []; $bMonthly = [];
        $r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS c FROM disaster_reports WHERE barangay_id=$bid GROUP BY ym ORDER BY ym");
        if ($r) while ($x = $r->fetch_assoc()) { $bMonths[] = date('M y', strtotime($x['ym'] . '-01')); $bMonthly[] = intval($x['c']); }
        $bDamage = $conn->query("SELECT SUM(damage_extent='Totally') AS t, SUM(damage_extent='Partially') AS p FROM disaster_reports WHERE barangay_id=$bid")->fetch_assoc();
        $per[$bid] = [
            'pop' => [intval($b['detail']['population'] ?? 0), intval($b['detail']['households'] ?? 0), intval($b['detail']['head_of_household'] ?? 0)],
            'months' => $bMonths,
            'monthly' => $bMonthly,
            'status' => $bStatus,
            'damage' => ['Totally' => intval($bDamage['t'] ?? 0), 'Partially' => intval($bDamage['p'] ?? 0)]
        ];
    }
}

$reliefSchedules = [];
$hasReliefTable = @in_array('relief_schedules', array_map(function($t){ return $t[0]; }, $conn->query("SHOW TABLES")->fetch_all()));
if ($hasReliefTable) {
    $reliefSchedules = $conn->query("SELECT schedule_id, title, distribution_date, distribution_time, status FROM relief_schedules ORDER BY distribution_date DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
}

if ($hasReportTable) {
    $recentReports = $conn->query("SELECT dr.*, b.barangay_name FROM disaster_reports dr LEFT JOIN barangays b ON dr.barangay_id = b.barangay_id ORDER BY dr.created_at DESC LIMIT 10");
} else {
    $recentReports = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; min-height:100vh; }
.wrapper { display:flex; min-height:100vh; }
.main-content { flex:1; margin-left:0; transition:margin-left 0.3s; min-height:100vh; background:#f5f7fa; }
.sidebar:not(.hide) + .main-content { margin-left:260px; }

.header { display:flex; justify-content:space-between; align-items:center; padding:18px 30px; background:linear-gradient(90deg,#0072C6,#005999); color:white; box-shadow:0 2px 10px rgba(0,0,0,0.1); position:relative; z-index:10; }
.header h1 { font-size:1.4rem; }
.admin-profile { display:flex; align-items:center; gap:10px; cursor:pointer; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.1); }
.admin-profile:hover { background:rgba(255,255,255,0.2); }
.admin-profile img { width:36px; height:36px; border-radius:50%; border:2px solid white; }
.dropdown { display:none; position:absolute; right:30px; top:65px; background:white; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.15); overflow:hidden; z-index:1002; min-width:150px; }
.dropdown.show { display:block; }
.dropdown a { display:flex; align-items:center; gap:10px; padding:12px 20px; text-decoration:none; color:#333; }
.dropdown a:hover { background:#f5f5f5; color:#0072C6; }

.tabs-bar { display:flex; background:#e8ecf0; padding:0; min-height:40px; align-items:flex-end; overflow-x:auto; border-bottom:2px solid #ddd; }
.tab { display:flex; align-items:center; gap:8px; padding:10px 18px; background:#dde2e8; border-radius:8px 8px 0 0; cursor:pointer; font-size:0.85rem; font-weight:500; color:#555; white-space:nowrap; margin-right:2px; transition:background 0.2s; position:relative; }
.tab:hover { background:#cdd3da; }
.tab.active { background:#f5f7fa; color:#0072C6; font-weight:700; border:2px solid #ddd; border-bottom:2px solid #f5f7fa; margin-bottom:-2px; }
.tab .close-tab { margin-left:8px; width:18px; height:18px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:11px; color:#888; transition:all 0.2s; }
.tab .close-tab:hover { background:#dc3545; color:white; }
.tab-home { padding:10px 18px; background:#0072C6; color:white; border-radius:8px 8px 0 0; cursor:pointer; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; }
.tab-home.active { background:#005999; }

.content { padding:25px 30px; }

.search-box { width:100%; max-width:500px; padding:12px 18px 12px 45px; border:2px solid #e0e0e0; border-radius:12px; font-size:1rem; transition:border-color 0.3s, box-shadow 0.3s; background:white; }
.search-box:focus { outline:none; border-color:#0072C6; box-shadow:0 0 0 3px rgba(0,114,198,0.1); }
.search-wrapper { position:relative; margin-bottom:20px; }
.search-wrapper i { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#999; font-size:1rem; }

.card { background:white; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.06); overflow:hidden; }
.card-header { padding:18px 25px; border-bottom:2px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
.card-header h2 { font-size:1.2rem; color:#333; }

table { width:100%; border-collapse:collapse; }
th, td { padding:14px 20px; text-align:left; border-bottom:1px solid #f0f0f0; }
th { color:#888; font-weight:600; font-size:0.8rem; text-transform:uppercase; background:#fafbfc; }
tr.data-row { cursor:pointer; transition:background 0.2s; }
tr.data-row:hover { background:#f0f7ff; }

.badge { padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
.badge-blue { background:#d6eaf8; color:#1a5276; }
.badge-orange { background:#fff3cd; color:#856404; }
.badge-green { background:#d4edda; color:#155724; }
.badge-red { background:#f8d7da; color:#721c24; }
.badge-gray { background:#e9ecef; color:#666; }
.badge-purple { background:#e8daef; color:#6f42c1; }

.reminder-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;}
.reminder-panel{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.reminder-header{padding:12px 18px;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;border-bottom:2px solid #f0f0f0;}
.reminder-body{padding:12px 18px;max-height:180px;overflow-y:auto;}
.reminder-item{padding:8px 0;border-bottom:1px solid #f5f5f5;display:flex;align-items:flex-start;gap:10px;}
.reminder-item:last-child{border-bottom:none;}
.reminder-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:6px;}
.reminder-dot.red{background:#dc3545;}
.reminder-dot.orange{background:#fd7e14;}
.reminder-dot.blue{background:#0072C6;}
.reminder-dot.green{background:#00D09C;}
.reminder-title{font-weight:600;font-size:0.82rem;color:#333;}
.reminder-sub{font-size:0.73rem;color:#999;margin-top:2px;}
.reminder-empty{padding:15px;text-align:center;color:#bbb;font-size:0.8rem;}
@media(max-width:900px){.reminder-row{grid-template-columns:1fr;}}

.chart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px;}
.chart-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.chart-card .card-header{padding:12px 18px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;}
.chart-wrap{position:relative;height:240px;padding:12px 14px 6px;}
.chart-empty{display:none;text-align:center;color:#a5afbd;padding:45px 10px;font-size:0.8rem;font-weight:600;}
.chart-empty i{display:block;font-size:2.2rem;color:#ccd5e0;margin-bottom:12px;}

/* ============ STATS STRIP ============ */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:22px;}
.stat-card{background:white;border-radius:16px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 4px 14px rgba(0,0,0,0.07);position:relative;overflow:hidden;transition:transform 0.2s, box-shadow 0.2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(0,0,0,0.12);}
.stat-card::after{content:'';position:absolute;right:-22px;top:-22px;width:84px;height:84px;border-radius:50%;opacity:.14;background:currentColor;}
.stat-card>i{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.18);}
.stat-card.sc-b{color:#0072C6;}
.stat-card.sc-b>i{background:linear-gradient(135deg,#0a6cff,#00a3ff);}
.stat-card.sc-r{color:#dc3545;}
.stat-card.sc-r>i{background:linear-gradient(135deg,#e53935,#ff7043);}
.stat-card.sc-o{color:#f57c00;}
.stat-card.sc-o>i{background:linear-gradient(135deg,#f57c00,#ffb300);}
.stat-card.sc-g{color:#28a745;}
.stat-card.sc-g>i{background:linear-gradient(135deg,#2e7d32,#66bb6a);}
.stat-card b{font-size:1.65rem;color:#222;display:block;line-height:1.1;}
.stat-card span{font-size:0.78rem;color:#8892a3;font-weight:600;}

/* ============ RISK REPORT ============ */
.risk-row{display:grid;grid-template-columns:1fr 1.25fr;gap:16px;margin-bottom:22px;}
.risk-panel{background:white;border-radius:16px;box-shadow:0 4px 14px rgba(0,0,0,0.07);overflow:hidden;}
.risk-head{display:flex;align-items:center;gap:9px;padding:15px 20px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.92rem;color:#333;}
.risk-head i{color:#0072C6;}
.risk-chart-wrap{position:relative;height:250px;padding:16px 14px 8px;}
.risk-donut-center{position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;}
.risk-donut-center b{display:block;font-size:1.9rem;line-height:1;color:#222;}
.risk-donut-center span{font-size:0.68rem;color:#8892a3;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.risk-list{padding:16px 20px 18px;}
.risk-line{display:flex;align-items:center;gap:11px;margin-bottom:12px;}
.r-ico{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.18);}
.r-ico.r-low{background:linear-gradient(135deg,#43e97b,#28a745);}
.r-ico.r-medium{background:linear-gradient(135deg,#ffb74d,#fd7e14);}
.r-ico.r-high{background:linear-gradient(135deg,#ff7a7a,#dc3545);}
.r-ico.r-critical{background:linear-gradient(135deg,#b34444,#7b1a1a);}
.risk-line .rname{min-width:58px;font-size:0.76rem;font-weight:700;text-align:right;}
.risk-line .rname.r-low{color:#28a745;}
.risk-line .rname.r-medium{color:#fd7e14;}
.risk-line .rname.r-high{color:#dc3545;}
.risk-line .rname.r-critical{color:#7b1a1a;}
.risk-bar{flex:1;height:12px;background:#eef1f4;border-radius:8px;overflow:hidden;}
.risk-bar-fill{height:100%;border-radius:8px;transition:width 0.7s ease;display:flex;align-items:center;justify-content:flex-end;color:#fff;font-size:0.62rem;font-weight:700;padding-right:6px;}
.risk-bar-fill.r-low{background:linear-gradient(90deg,#43e97b,#28a745);}
.risk-bar-fill.r-medium{background:linear-gradient(90deg,#ffb74d,#fd7e14);}
.risk-bar-fill.r-high{background:linear-gradient(90deg,#ff7a7a,#dc3545);}
.risk-bar-fill.r-critical{background:linear-gradient(90deg,#b34444,#7b1a1a);}
.risk-count{font-weight:700;font-size:0.92rem;color:#222;min-width:22px;text-align:right;}
.risk-names{font-size:0.78rem;color:#5a6673;margin:3px 0 12px;padding-left:4px;line-height:1.55;}
.risk-names-lbl{font-weight:700;margin-right:4px;}
.risk-names-lbl.r-critical-txt{color:#7b1a1a;}
.risk-names-lbl.r-high-txt{color:#dc3545;}
.risk-names-lbl.r-medium-txt{color:#fd7e14;}
.risk-names-lbl.r-low-txt{color:#28a745;}
.risk-legend-total{display:flex;align-items:center;justify-content:space-between;margin-top:6px;padding:10px 14px;background:#f8fafc;border-radius:10px;font-size:0.78rem;color:#5a6673;font-weight:600;}
@media(max-width:900px){.risk-row{grid-template-columns:1fr;}}

/* ============ HEADER BRAND ============ */
.header-brand{display:flex;align-items:center;gap:12px;}
.header-brand img{width:44px;height:44px;border-radius:12px;object-fit:cover;border:2px solid rgba(255,255,255,.5);background:#fff;padding:2px;}
.header-brand .hb-txt b{display:block;font-size:1.15rem;line-height:1.1;}
.header-brand .hb-txt span{font-size:0.72rem;color:rgba(255,255,255,.8);}

.tab-content { display:none; }
.tab-content.active { display:block; }

.detail-header { background:linear-gradient(135deg,#0072C6,#005999); color:white; padding:25px 30px; border-radius:12px; margin-bottom:20px; }
.detail-header h2 { font-size:1.6rem; margin-bottom:5px; }
.detail-header p { opacity:0.85; }

.stat-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:15px; margin-bottom:25px; }
.stat-mini { background:white; border-radius:12px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,0.06); text-align:center; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s; }
.stat-mini:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,0.1); }
.stat-mini .num { font-size:1.8rem; font-weight:700; color:#0072C6; }
.stat-mini .label { font-size:0.85rem; color:#888; margin-top:4px; }

.info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:15px; margin-bottom:25px; }
.info-card { background:white; border-radius:12px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
.info-card h4 { color:#0072C6; margin-bottom:8px; font-size:0.95rem; }
.info-card p { color:#555; font-size:0.9rem; line-height:1.5; }

.report-list { max-height:400px; overflow-y:auto; }
.report-item { display:flex; justify-content:space-between; align-items:center; padding:12px 18px; border-bottom:1px solid #f0f0f0; transition:background 0.2s; }
.report-item:hover { background:#f9f9f9; }
.report-item:last-child { border-bottom:none; }
.report-item .info { flex:1; }
.report-item .info strong { color:#333; }
.report-item .info span { display:block; font-size:0.8rem; color:#999; margin-top:2px; }

.empty-state { text-align:center; padding:50px 20px; color:#aaa; }
.empty-state i { font-size:3rem; margin-bottom:15px; display:block; }
</style>
</head>
<body>

<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <div class="header-brand">
                <img src="mapa.png" alt="Logo">
                <div class="hb-txt"><b>DSWD - Municipal Office</b><span>Data Management System</span></div>
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

        <div class="tabs-bar" id="tabsBar">
            <div class="tab-home active" id="tabHome" onclick="showHome()">
                <i class="fas fa-home"></i> Dashboard
            </div>
        </div>

        <div class="content">
            <!-- HOME VIEW -->
            <div class="tab-content active" id="viewHome">
                <!-- Stats -->
                <div class="stat-row">
                    <div class="stat-card sc-b"><i class="fas fa-building"></i><div><b><?php echo $totalBarangays; ?></b><span>Total Barangays</span></div></div>
                    <div class="stat-card sc-r"><i class="fas fa-exclamation-triangle"></i><div><b><?php echo $highCriticalCount; ?></b><span>High / Critical Risk</span></div></div>
                    <div class="stat-card sc-o"><i class="fas fa-file-alt"></i><div><b><?php echo $totalReportsAll; ?></b><span>Total Disaster Reports</span></div></div>
                    <div class="stat-card sc-g"><i class="fas fa-home"></i><div><b><?php echo number_format($totalHouseholdsAll); ?></b><span>Total Households</span></div></div>
                </div>

                <!-- Risk Level Report -->
                <div class="risk-row">
                    <div class="risk-panel">
                        <div class="risk-head"><i class="fas fa-chart-pie" style="color:#7c5cff;"></i> Risk Level Distribution</div>
                        <div class="risk-chart-wrap">
                            <canvas id="riskChart"></canvas>
                            <div class="risk-donut-center"><b><?php echo $totalBarangays; ?></b><span>Barangays</span></div>
                        </div>
                    </div>
                    <div class="risk-panel">
                        <div class="risk-head"><i class="fas fa-list-check" style="color:#0072C6;"></i> Barangay Risk Overview</div>
                        <div class="risk-list">
                            <?php
                            $riskLevelsOrder = ['Critical', 'High', 'Medium', 'Low'];
                            $riskIcons = ['Critical' => 'fa-skull-crossbones', 'High' => 'fa-triangle-exclamation', 'Medium' => 'fa-exclamation', 'Low' => 'fa-circle-check'];
                            foreach ($riskLevelsOrder as $lvl):
                                $pct = $totalBarangays ? round($riskCounts[$lvl] / $totalBarangays * 100) : 0;
                            ?>
                            <div class="risk-line">
                                <span class="r-ico r-<?php echo strtolower($lvl); ?>"><i class="fas <?php echo $riskIcons[$lvl]; ?>"></i></span>
                                <span class="rname r-<?php echo strtolower($lvl); ?>"><?php echo $lvl; ?></span>
                                <div class="risk-bar"><div class="risk-bar-fill r-<?php echo strtolower($lvl); ?>" style="width:<?php echo $pct; ?>%;"><?php echo $riskCounts[$lvl] ?: ''; ?></div></div>
                                <span class="risk-count"><?php echo $riskCounts[$lvl]; ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($totalBarangays): foreach ($riskLevelsOrder as $lvl): if (count($riskBarangays[$lvl])): ?>
                            <div class="risk-names"><span class="risk-names-lbl r-<?php echo strtolower($lvl); ?>-txt"><?php echo $lvl; ?>:</span> <?php echo htmlspecialchars(implode(', ', $riskBarangays[$lvl])); ?></div>
                            <?php endif; endforeach; else: ?>
                            <div class="risk-names">No barangays found.</div>
                            <?php endif; ?>
                            <div class="risk-legend-total"><span><i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i> High / Critical</span><b style="color:#dc3545;"><?php echo $highCriticalCount; ?></b></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Reminders -->
                <div class="reminder-row">
                    <div class="reminder-panel">
                        <div class="reminder-header"><i class="fas fa-bullhorn" style="color:#dc3545;"></i> Latest Announcements</div>
                        <div class="reminder-body">
                            <?php if (empty($announcements)): ?>
                            <div class="reminder-empty"><i class="fas fa-inbox"></i> No announcements yet</div>
                            <?php else: foreach ($announcements as $a): ?>
                            <div class="reminder-item">
                                <div class="reminder-dot <?php echo $a['is_pinned'] ? 'red' : 'blue'; ?>"></div>
                                <div>
                                    <div class="reminder-title"><?php echo htmlspecialchars($a['title']); ?></div>
                                    <div class="reminder-sub"><?php echo htmlspecialchars(substr($a['message'], 0, 80)); ?><?php echo strlen($a['message']) > 80 ? '...' : ''; ?></div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <div class="reminder-panel">
                        <div class="reminder-header"><i class="fas fa-box-open" style="color:#fd7e14;"></i> Relief Goods Distribution</div>
                        <div class="reminder-body">
                            <?php if (empty($reliefSchedules)): ?>
                            <div class="reminder-empty"><i class="fas fa-calendar"></i> No schedules yet</div>
                            <?php else: foreach ($reliefSchedules as $s): 
                                $d = date('M d, Y', strtotime($s['distribution_date']));
                                $cls = $s['status'] === 'completed' ? 'green' : ($s['status'] === 'ongoing' ? 'orange' : 'blue');
                            ?>
                            <div class="reminder-item">
                                <div class="reminder-dot <?php echo $cls; ?>"></div>
                                <div>
                                    <div class="reminder-title"><?php echo htmlspecialchars($s['title']); ?></div>
                                    <div class="reminder-sub"><?php echo $d; ?> · <?php echo ucfirst($s['status']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- VISUAL REPORTS -->
                <div class="chart-row">
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-users" style="color:#7c5cff;"></i> Population, Households &amp; Heads of Household</div>
                        <div class="chart-wrap"><canvas id="popChart"></canvas></div>
                        <div class="chart-empty" id="popChartEmpty"><i class="fas fa-users"></i>No barangay demographics yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-clipboard-check" style="color:#0072C6;"></i> Disaster Reports by Status</div>
                        <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
                        <div class="chart-empty" id="statusChartEmpty"><i class="fas fa-clipboard-check"></i>No disaster reports yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent (Totally vs Partially)</div>
                        <div class="chart-wrap"><canvas id="damageChart"></canvas></div>
                        <div class="chart-empty" id="damageChartEmpty"><i class="fas fa-house-damage"></i>No damage data yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-file-alt" style="color:#fd7e14;"></i> Disaster Reports by Barangay</div>
                        <div class="chart-wrap"><canvas id="reportChart"></canvas></div>
                        <div class="chart-empty" id="reportChartEmpty"><i class="fas fa-file-alt"></i>No reports yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-chart-line" style="color:#6f42c1;"></i> Status by Barangay</div>
                        <div class="chart-wrap"><canvas id="statusByBrgyChart"></canvas></div>
                        <div class="chart-empty" id="statusByBrgyChartEmpty"><i class="fas fa-chart-line"></i>No reports yet</div>
                    </div>
                </div>

                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-box" id="searchBox" placeholder="Search barangay..." oninput="filterBarangays()">
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-building"></i> All Barangays</h2>
                        <span style="color:#999; font-size:0.9rem;"><?php echo count($barangayList); ?> total</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Barangay Name</th>
                                <th>Households</th>
                                <th>Reports</th>
                                <th>Pending</th>
                                <th>Approved</th>
                                <th>Declined</th>
                                <th>Contacts</th>
                            </tr>
                        </thead>
                        <tbody id="barangayTable">
                            <?php foreach ($barangayList as $b): ?>
                            <tr class="data-row" onclick="openBarangayTab(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars(addslashes($b['name'])); ?>')">
                                <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                                <td><span class="badge badge-purple"><?php echo number_format($b['households']); ?></span></td>
                                <td><span class="badge badge-blue"><?php echo $b['reports']; ?></span></td>
                                <td><span class="badge badge-orange"><?php echo $b['pending']; ?></span></td>
                                <td><span class="badge badge-green"><?php echo $b['approved']; ?></span></td>
                                <td><span class="badge badge-red"><?php echo $b['declined']; ?></span></td>
                                <td><span class="badge badge-gray"><?php echo $b['contacts']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($barangayList)): ?>
                            <tr><td colspan="7" class="empty-state"><i class="fas fa-building"></i>No barangays found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BARANGAY DETAIL VIEWS (hidden, generated per barangay) -->
            <?php foreach ($barangayList as $b):
                $bid = $b['id'];
                $d = $b['detail'];
                $reports = null;
                if ($hasReportTable) {
                    $reports = $conn->query("SELECT * FROM disaster_reports WHERE barangay_id=$bid ORDER BY created_at DESC");
                }
                $contacts = $conn->query("SELECT * FROM barangay_contacts WHERE barangay_id=$bid")->fetch_assoc();
            ?>
            <div class="tab-content" id="view_<?php echo $bid; ?>">
                <div class="detail-header">
                    <h2><i class="fas fa-building"></i> <?php echo htmlspecialchars($b['name']); ?></h2>
                    <p><?php echo htmlspecialchars($b['address'] ?: 'Municipality of Malilipot'); ?></p>
                </div>

                <div class="stat-row">
                    <div class="stat-mini" onclick="showSection(<?php echo $bid; ?>, 'reports')">
                        <div class="num"><?php echo $b['reports']; ?></div>
                        <div class="label">Total Reports</div>
                    </div>
                    <div class="stat-mini">
                        <div class="num" style="color:#fd7e14;"><?php echo $b['pending']; ?></div>
                        <div class="label">Pending</div>
                    </div>
                    <div class="stat-mini">
                        <div class="num" style="color:#28a745;"><?php echo $b['approved']; ?></div>
                        <div class="label">Approved</div>
                    </div>
                    <div class="stat-mini">
                        <div class="num" style="color:#dc3545;"><?php echo $b['declined']; ?></div>
                        <div class="label">Declined</div>
                    </div>
                    <div class="stat-mini" onclick="showSection(<?php echo $bid; ?>, 'info')">
                        <div class="num" style="color:#666;"><i class="fas fa-info-circle"></i></div>
                        <div class="label">Barangay Info</div>
                    </div>
                </div>

                <!-- Per-Barangay Visual Report -->
                <div class="chart-row" style="margin-bottom:20px;">
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-users" style="color:#7c5cff;"></i> Population &amp; Households</div>
                        <div class="chart-wrap"><canvas id="vpop_<?php echo $bid; ?>"></canvas></div>
                        <div class="chart-empty" id="vpopE_<?php echo $bid; ?>"><i class="fas fa-users"></i>No demographic data yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-chart-line" style="color:#0072C6;"></i> Disaster Reports Over Time</div>
                        <div class="chart-wrap"><canvas id="vtime_<?php echo $bid; ?>"></canvas></div>
                        <div class="chart-empty" id="vtimeE_<?php echo $bid; ?>"><i class="fas fa-chart-line"></i>No reports yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-clipboard-check" style="color:#28a745;"></i> Reports by Status</div>
                        <div class="chart-wrap"><canvas id="vstatus_<?php echo $bid; ?>"></canvas></div>
                        <div class="chart-empty" id="vstatusE_<?php echo $bid; ?>"><i class="fas fa-clipboard-check"></i>No reports yet</div>
                    </div>
                    <div class="chart-card">
                        <div class="card-header"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent</div>
                        <div class="chart-wrap"><canvas id="vdamage_<?php echo $bid; ?>"></canvas></div>
                        <div class="chart-empty" id="vdamageE_<?php echo $bid; ?>"><i class="fas fa-house-damage"></i>No damage data yet</div>
                    </div>
                </div>

                <!-- Reports Section -->
                <div id="section_reports_<?php echo $bid; ?>" class="card" style="margin-bottom:20px; display:block;">
                    <div class="card-header">
                        <h2><i class="fas fa-file-alt"></i> Disaster Reports</h2>
                    </div>
                    <div class="report-list">
                        <?php if ($reports && $reports->num_rows > 0): ?>
                            <?php while ($rep = $reports->fetch_assoc()): ?>
                            <div class="report-item">
                                <div class="info">
                                    <strong><?php echo htmlspecialchars($rep['title'] ?? 'Untitled'); ?></strong>
                                    <span><?php echo htmlspecialchars($rep['disaster_type'] ?? ''); ?> | <?php echo date('M d, Y', strtotime($rep['created_at'])); ?></span>
                                </div>
                                <span class="badge <?php echo $rep['status']=='approved'?'badge-green':($rep['status']=='declined'?'badge-red':'badge-orange'); ?>"><?php echo ucfirst($rep['status']); ?></span>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-inbox"></i>No reports yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barangay Info Section -->
                <div id="section_info_<?php echo $bid; ?>" class="card" style="margin-bottom:20px; display:block;">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Barangay Information</h2>
                    </div>
                    <div style="padding:20px;">
                        <div class="info-grid">
                            <div class="info-card"><h4>Barangay Code</h4><p><?php echo htmlspecialchars($d['barangay_code'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Municipality</h4><p><?php echo htmlspecialchars($d['municipality'] ?? 'Malilipot'); ?></p></div>
                            <div class="info-card"><h4>Province</h4><p><?php echo htmlspecialchars($d['province'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Region</h4><p><?php echo htmlspecialchars($d['region'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Zip Code</h4><p><?php echo htmlspecialchars($d['zip_code'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Barangay Captain</h4><p><?php echo htmlspecialchars($d['captain_name'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Secretary</h4><p><?php echo htmlspecialchars($d['secretary_name'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Treasurer</h4><p><?php echo htmlspecialchars($d['treasurer_name'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Population</h4><p><?php echo htmlspecialchars($d['population'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Total Households</h4><p><?php echo htmlspecialchars($d['households'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Land Area</h4><p><?php echo htmlspecialchars($d['land_area'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Boundaries</h4><p><?php echo htmlspecialchars($d['boundaries'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Barangay Hall Address</h4><p><?php echo htmlspecialchars($d['barangay_hall_address'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Health Center</h4><p><?php echo htmlspecialchars($d['health_center'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Date Established</h4><p><?php echo htmlspecialchars($d['date_established'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Emergency Services</h4><p><?php echo htmlspecialchars($d['emergency_services'] ?? 'N/A'); ?></p></div>
                        </div>

                        <?php if ($contacts): ?>
                        <h3 style="margin:20px 0 12px; color:#0072C6;"><i class="fas fa-phone"></i> Emergency Contacts</h3>
                        <div class="info-grid">
                            <div class="info-card"><h4>Barangay Hall</h4><p><?php echo htmlspecialchars($contacts['barangay_hall_phone'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Chairman</h4><p><?php echo htmlspecialchars($contacts['barangay_chairman_phone'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Secretary</h4><p><?php echo htmlspecialchars($contacts['barangay_secretary_phone'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Tanod</h4><p><?php echo htmlspecialchars($contacts['barangay_tanod_phone'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Police</h4><p><?php echo htmlspecialchars($contacts['police_hotline'] ?? 'N/A'); ?></p></div>
                            <div class="info-card"><h4>Fire Department</h4><p><?php echo htmlspecialchars($contacts['fire_hotline'] ?? 'N/A'); ?></p></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
let openTabs = {};

function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("show");
}
window.onclick = function(e) {
    if (!e.target.closest('.admin-profile')) {
        document.getElementById("dropdownMenu").classList.remove("show");
    }
}

function filterBarangays() {
    const query = document.getElementById('searchBox').value.toLowerCase();
    const rows = document.querySelectorAll('#barangayTable .data-row');
    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        row.style.display = name.includes(query) ? '' : 'none';
    });
}

function showHome() {
    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    document.getElementById('viewHome').classList.add('active');
    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));
    document.getElementById('tabHome').classList.add('active');
}

function openBarangayTab(id, name) {
    if (!document.getElementById('view_' + id)) return;

    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    document.getElementById('view_' + id).classList.add('active');

    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));

    if (!openTabs[id]) {
        const tab = document.createElement('div');
        tab.className = 'tab active';
        tab.id = 'tab_' + id;
        tab.innerHTML = '<i class="fas fa-building"></i> ' + name + ' <span class="close-tab" onclick="event.stopPropagation(); closeTab(' + id + ')">&times;</span>';
        tab.onclick = function() { openBarangayTab(id, name); };
        document.getElementById('tabsBar').appendChild(tab);
        openTabs[id] = true;
    } else {
        document.getElementById('tab_' + id).classList.add('active');
    }

    document.getElementById('tabHome').classList.remove('active');
    renderBarangayCharts(id);
}

function closeTab(id) {
    const tab = document.getElementById('tab_' + id);
    if (tab) tab.remove();
    delete openTabs[id];

    const view = document.getElementById('view_' + id);
    if (view) view.classList.remove('active');

    const remaining = document.querySelectorAll('.tab');
    if (remaining.length > 0) {
        remaining[remaining.length - 1].click();
    } else {
        showHome();
    }
}

function showSection(bid, section) {
    ['reports','info'].forEach(s => {
        const el = document.getElementById('section_' + s + '_' + bid);
        if (el) el.style.display = (s === section) ? 'block' : (section === 'all' ? 'block' : 'none');
    });
}

const CH = <?php echo json_encode($chart_json); ?>;
const C_COLORS = ['#0072C6','#28a745','#fd7e14','#dc3545','#6f42c1','#20c997','#ffc107','#e83e8c','#17a2b8','#6c757d','#e8710a','#0097a7','#00c853','#5f6368'];
function cColors(n){ return Array.from({length:n},(_,i)=>C_COLORS[i%C_COLORS.length]); }
function mountChart(canvasId, emptyId, type, labels, datasets, opts){
    const c = document.getElementById(canvasId);
    const has = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
    if (!has) { c.style.display = 'none'; document.getElementById(emptyId).style.display = 'block'; return; }
    new Chart(c, {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                tooltip: { callbacks: { label: (ctx) => {
                    const t = ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const p = t ? ((ctx.parsed / t) * 100).toFixed(1) : 0;
                    return ' ' + ctx.label + ': ' + ctx.parsed + (ctx.dataset.label ? ' ' + ctx.dataset.label : '') + ' (' + p + '%)';
                } } }
            }
        }, opts || {})
    });
}
const statusMeta = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
const sLbl = [], sDat = [], sCol = [];
statusMeta.forEach(m => { if (CH.status[m[0]] > 0) { sLbl.push(m[1]); sDat.push(CH.status[m[0]]); sCol.push(m[2]); } });

mountChart('popChart','popChartEmpty','line', CH.barangays, [
    { label: 'Population', data: CH.population, borderColor: '#7c5cff', backgroundColor: 'rgba(124,92,255,0.12)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 },
    { label: 'Households', data: CH.households, borderColor: '#0072C6', backgroundColor: 'rgba(0,114,198,0.12)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 },
    { label: 'Head of Household', data: CH.head, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.12)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 }
], { scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } } } });

mountChart('statusChart','statusChartEmpty','doughnut', sLbl, [{ data: sDat, backgroundColor: sCol, borderWidth: 2, borderColor: '#fff' }], { plugins: { legend: { position: 'bottom' } } });

mountChart('damageChart','damageChartEmpty','doughnut', ['Totally','Partially'], [{ data: [CH.damage.Totally, CH.damage.Partially], backgroundColor: ['#dc3545','#fd7e14'], borderWidth: 2, borderColor: '#fff' }], { plugins: { legend: { position: 'bottom' } } });

mountChart('reportChart','reportChartEmpty','line', CH.barangays, [
    { label: 'Reports', data: CH.reports, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.15)', fill: true, tension: 0.3, pointRadius: 5, borderWidth: 2 }
], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's') } } } });

mountChart('statusByBrgyChart','statusByBrgyChartEmpty','line', CH.status_by_brgy.labels,
    statusMeta.map(m => ({ label: m[1], data: CH.status_by_brgy[m[0]], borderColor: m[2], backgroundColor: m[2] + '22', fill: false, tension: 0.3, pointRadius: 4, borderWidth: 2 })),
    { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } } } });

/* Risk level donut */
(function initRiskChart(){
    const el = document.getElementById('riskChart');
    if (!el || typeof Chart === 'undefined') return;
    const labels = ['Critical','High','Medium','Low'];
    const colors = ['#7b1a1a','#dc3545','#fd7e14','#28a745'];
    const data = labels.map(l => CH.risk[l] || 0);
    const total = data.reduce((a,b)=>a+b,0) || 1;
    const shown = labels.filter((l,i)=>data[i]>0);
    if (!shown.length) { el.style.display='none'; return; }
    new Chart(el, {
        type: 'doughnut',
        data: { labels: shown, datasets: [{ data: data.filter(d=>d>0), backgroundColor: colors.filter((c,i)=>data[i]>0), borderWidth: 2, borderColor: '#fff', hoverOffset: 8 }] },
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

/* Per-barangay detail charts */
const PER = <?php echo json_encode($per); ?>;
let bCharts = {};
const BSTATUS_META = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
function bMount(id, canvasId, emptyId, type, labels, datasets, opts){
    const c = document.getElementById(canvasId);
    const has = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
    if (!has) { c.style.display = 'none'; document.getElementById(emptyId).style.display = 'block'; return null; }
    return new Chart(c, {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                tooltip: { callbacks: { label: (ctx) => {
                    const t = ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const p = t ? ((ctx.parsed.y || ctx.parsed) / t * 100).toFixed(1) : 0;
                    return ' ' + ctx.label + ': ' + (ctx.parsed.y || ctx.parsed) + ' (' + p + '%)';
                } } }
            }
        }, opts || {})
    });
}
function renderBarangayCharts(id){
    if (!PER[id]) return;
    (bCharts[id] || []).forEach(c => { if (c) c.destroy(); });
    bCharts[id] = [];
    const d = PER[id];

    const dLab = ['Population','Households','Head of Household'];
    const c1 = bMount(id,'vpop_'+id,'vpopE_'+id,'line', dLab, [{ label: 'Count', data: d.pop, borderColor: '#7c5cff', backgroundColor: 'rgba(124,92,255,0.12)', fill: true, tension: 0.3, pointRadius: 5, borderWidth: 2 }]);
    if (c1) bCharts[id].push(c1);

    const c2 = bMount(id,'vtime_'+id,'vtimeE_'+id,'line', d.months, [{ label: 'Reports', data: d.monthly, borderColor: '#0072C6', backgroundColor: 'rgba(0,114,198,0.15)', fill: true, tension: 0.3, pointRadius: 5, borderWidth: 2 }], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's') } } } });
    if (c2) bCharts[id].push(c2);

    const sLbl = [], sDat = [], sCol = [];
    BSTATUS_META.forEach(m => { if (d.status[m[0]] > 0) { sLbl.push(m[1]); sDat.push(d.status[m[0]]); sCol.push(m[2]); } });
    const c3 = bMount(id,'vstatus_'+id,'vstatusE_'+id,'doughnut', sLbl, [{ data: sDat, backgroundColor: sCol, borderWidth: 2, borderColor: '#fff' }]);
    if (c3) bCharts[id].push(c3);

    const c4 = bMount(id,'vdamage_'+id,'vdamageE_'+id,'doughnut', ['Totally','Partially'], [{ data: [d.damage.Totally, d.damage.Partially], backgroundColor: ['#dc3545','#fd7e14'], borderWidth: 2, borderColor: '#fff' }]);
    if (c4) bCharts[id].push(c4);
}
</script>

</body>
</html>
