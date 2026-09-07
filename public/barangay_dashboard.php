<?php
session_start();
if (!isset($_SESSION['barangay_name'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';

$barangay_id = $_SESSION['barangay_id'] ?? 0;
$barangay_name = $_SESSION['barangay_name'] ?? 'Barangay';

// Fetch barangay details
$stmt = $conn->prepare("SELECT bd.* FROM barangay_details bd WHERE bd.barangay_id = ?");
$stmt->bind_param("i", $barangay_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc() ?? [];

// Fetch contacts
$contacts = [];
$stmt2 = $conn->prepare("SELECT * FROM barangay_contacts WHERE barangay_id = ?");
$stmt2->bind_param("i", $barangay_id);
$stmt2->execute();
$contacts = $stmt2->get_result()->fetch_assoc() ?? [];

// Merge municipal contacts (hotlines etc.)
$muni = $conn->query("SELECT * FROM municipal_contacts LIMIT 1")->fetch_assoc() ?? [];
foreach ($muni as $k => $v) {
    if (!isset($contacts[$k]) || $contacts[$k] === null || $contacts[$k] === '') {
        $contacts[$k] = $v;
    }
}

// Fetch report counts
$reportCount = 0;
$hasReportTable = @in_array('disaster_reports', array_map(function($t){ return $t[0]; }, $conn->query("SHOW TABLES")->fetch_all()));
if ($hasReportTable) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM disaster_reports WHERE barangay_id=$barangay_id");
    if ($r) $reportCount = $r->fetch_assoc()['cnt'];
}

// Chart data
$statusCounts = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
$damageTotally = 0; $damagePartially = 0;
if ($hasReportTable) {
    $r = $conn->query("SELECT status, COUNT(*) AS c FROM disaster_reports WHERE barangay_id=$barangay_id GROUP BY status");
    if ($r) while ($x = $r->fetch_assoc()) if (isset($statusCounts[$x['status']])) $statusCounts[$x['status']] = intval($x['c']);
    $r = $conn->query("SELECT SUM(damage_extent='Totally') AS t, SUM(damage_extent='Partially') AS p FROM disaster_reports WHERE barangay_id=$barangay_id");
    if ($r) { $dam = $r->fetch_assoc(); $damageTotally = intval($dam['t']); $damagePartially = intval($dam['p']); }
}
$barangay_chart_json = [
    'population' => intval($details['population'] ?? 0),
    'households' => intval($details['households'] ?? 0),
    'head' => intval($details['head_of_household'] ?? 0),
    'status' => $statusCounts,
    'damage' => ['Totally' => $damageTotally, 'Partially' => $damagePartially]
];

$riskLevel = $details['risk_level'] ?? 'Low';
if (!in_array($riskLevel, ['Low', 'Medium', 'High', 'Critical'])) $riskLevel = 'Low';
$riskMeta = [
    'Low'      => ['pct' => 25, 'color' => '#28a745', 'track' => '#e6f7eb', 'icon' => 'fa-circle-check',       'desc' => 'Minimal exposure to hazards. Continue monitoring and keep emergency plans updated.'],
    'Medium'   => ['pct' => 50, 'color' => '#fd7e14', 'track' => '#fff1e0', 'icon' => 'fa-exclamation',        'desc' => 'Moderate exposure to hazards. Strengthen preparedness and coordinate with DRRMO.'],
    'High'     => ['pct' => 75, 'color' => '#dc3545', 'track' => '#fde8e8', 'icon' => 'fa-triangle-exclamation','desc' => 'High exposure to hazards. Activate community response protocols and evacuations.'],
    'Critical' => ['pct' => 100, 'color' => '#7b1a1a', 'track' => '#f3e1e1', 'icon' => 'fa-skull-crossbones',   'desc' => 'Extreme hazard exposure. Immediate DRRMO coordination and evacuation readiness required.']
];
$riskCur = $riskMeta[$riskLevel];
$riskLevelsOrder = ['Critical', 'High', 'Medium', 'Low'];

$infoCats = [
    'identification' => [
        ['fa-barcode', '#0a6cff', 'Barangay Code', $details['barangay_code'] ?? 'N/A'],
        ['fa-city', '#7c5cff', 'Municipality', $details['municipality'] ?? 'Malilipot'],
        ['fa-map', '#e8710a', 'Province', $details['province'] ?? 'N/A'],
        ['fa-globe', '#17a2b8', 'Region', $details['region'] ?? 'N/A'],
        ['fa-envelope', '#28a745', 'Zip Code', $details['zip_code'] ?? 'N/A'],
    ],
    'leadership' => [
        ['fa-user-tie', '#0a6cff', 'Captain', $details['captain_name'] ?? 'N/A'],
        ['fa-user', '#28a745', 'Secretary', $details['secretary_name'] ?? 'N/A'],
        ['fa-wallet', '#e8710a', 'Treasurer', $details['treasurer_name'] ?? 'N/A'],
        ['fa-users', '#7c5cff', 'Councilors', $details['councilors'] ?? 'N/A'],
    ],
    'location' => [
        ['fa-border-all', '#0a6cff', 'Boundaries', $details['boundaries'] ?? 'N/A'],
        ['fa-road', '#e8710a', 'Streets / Puroks', $details['streets'] ?? 'N/A'],
        ['fa-ruler-combined', '#17a2b8', 'Land Area', $details['land_area'] ?? 'N/A'],
        ['fa-map-pin', '#dc3545', 'GPS Coordinates', $details['gps_coordinates'] ?? 'N/A'],
    ],
    'facilities' => [
        ['fa-landmark', '#0a6cff', 'Barangay Hall Address', $details['barangay_hall_address'] ?? 'N/A'],
        ['fa-hospital', '#28a745', 'Health Center', $details['health_center'] ?? 'N/A'],
        ['fa-school', '#e8710a', 'Daycare/Schools', $details['daycare_schools'] ?? 'N/A'],
        ['fa-ambulance', '#dc3545', 'Emergency Services', $details['emergency_services'] ?? 'N/A'],
    ],
    'other' => [
        ['fa-calendar-check', '#0a6cff', 'Date Established', $details['date_established'] ?? 'N/A'],
        ['fa-globe', '#17a2b8', 'Website', $details['website'] ?? 'N/A'],
        ['fa-scroll', '#e8710a', 'Ordinances', $details['ordinances'] ?? 'N/A'],
    ],
];

$contactGroups = [
    ['Barangay Contacts', 'fa-building', 'linear-gradient(135deg,#0a6cff,#005999)',
        [['Barangay Hall', $contacts['barangay_hall_phone'] ?? 'N/A'],
         ['Chairman', $contacts['barangay_chairman_phone'] ?? 'N/A'],
         ['Secretary', $contacts['barangay_secretary_phone'] ?? 'N/A'],
         ['Tanod', $contacts['barangay_tanod_phone'] ?? 'N/A']]],
    ['Municipal Hotlines', 'fa-city', 'linear-gradient(135deg,#fd7e14,#e8590c)',
        [['Hotline', $contacts['city_hotline'] ?? 'N/A'],
         ['DRRMO', $contacts['drrmo_hotline'] ?? 'N/A'],
         ['Police', $contacts['police_hotline'] ?? 'N/A'],
         ['Fire Dept', $contacts['fire_hotline'] ?? 'N/A']]],
    ['Medical & Emergency', 'fa-heartbeat', 'linear-gradient(135deg,#dc3545,#b91c1c)',
        [['Ambulance', $contacts['medical_services'] ?? 'N/A'],
         ['Hospital', $contacts['hospital_emergency'] ?? 'N/A'],
         ['Power', $contacts['power_emergency'] ?? 'N/A'],
         ['Water', $contacts['water_emergency'] ?? 'N/A']]],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Dashboard - <?php echo htmlspecialchars($barangay_name); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; min-height:100vh; }
.wrapper { display:flex; min-height:100vh; }
.main-content { flex:1; transition:margin-left 0.3s; min-height:100vh; }
.sidebar:not(.hide) + .main-content { margin-left:260px; }

.header { display:flex; justify-content:space-between; align-items:center; padding:18px 30px; background:linear-gradient(90deg,#0072C6,#005999); color:white; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.header h1 { font-size:1.4rem; }
.profile-area { display:flex; align-items:center; gap:10px; cursor:pointer; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.15); }
.profile-area:hover { background:rgba(255,255,255,0.25); }
.profile-area img { width:36px; height:36px; border-radius:50%; border:2px solid white; }
.dropdown { display:none; position:absolute; right:30px; top:65px; background:white; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.15); overflow:hidden; z-index:1002; min-width:150px; }
.dropdown.show { display:block; }
.dropdown a { display:flex; align-items:center; gap:10px; padding:12px 20px; text-decoration:none; color:#333; }
.dropdown a:hover { background:#f5f5f5; color:#0072C6; }

.content { padding:25px 30px; }

.welcome-banner { background:linear-gradient(135deg,#0072C6,#005999); color:white; border-radius:15px; padding:30px; margin-bottom:25px; box-shadow:0 4px 15px rgba(0,114,198,0.3); display:flex; align-items:center; gap:22px; position:relative; overflow:hidden; }
.welcome-banner::after{content:'';position:absolute;right:-60px;top:-70px;width:230px;height:230px;border-radius:50%;background:rgba(255,255,255,.08);}
.welcome-banner::before{content:'';position:absolute;right:40px;bottom:-90px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.wb-logo{width:86px;height:86px;border-radius:50%;object-fit:cover;background:#fff;padding:6px;box-shadow:0 6px 18px rgba(0,0,0,.3);flex-shrink:0;position:relative;z-index:1;}
.wb-main{position:relative;z-index:1;flex:1;}
.welcome-banner h2 { font-size:1.6rem; margin-bottom:5px; }
.welcome-banner p { opacity:0.9; }
.risk-chip{display:inline-flex;align-items:center;gap:7px;margin-top:16px;padding:7px 17px;border-radius:20px;font-size:0.8rem;font-weight:700;letter-spacing:.3px;background:rgba(255,255,255,.14);border:1.5px solid rgba(255,255,255,.35);}
.risk-chip.risk-low{border-color:#4ade80;}
.risk-chip.risk-medium{border-color:#fbbf24;}
.risk-chip.risk-high{border-color:#f87171;}
.risk-chip.risk-critical{border-color:#ff4d4d;animation:riskPulse 1.6s infinite;}
@keyframes riskPulse{0%,100%{opacity:1;}50%{opacity:.55;}}

/* ============ RISK REPORT ============ */
.risk-row{display:grid;grid-template-columns:0.9fr 1.2fr;gap:16px;margin-bottom:25px;}
.risk-panel{background:white;border-radius:16px;box-shadow:0 4px 14px rgba(0,0,0,0.07);overflow:hidden;}
.risk-head{display:flex;align-items:center;gap:9px;padding:15px 20px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.92rem;color:#333;}
.risk-head i{color:#0072C6;}
.risk-chart-wrap{position:relative;height:250px;padding:16px 14px 8px;}
.risk-gauge-center{position:absolute;top:52%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;}
.rg-ico{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;box-shadow:0 6px 16px rgba(0,0,0,.22);}
.risk-gauge-center b{display:block;font-size:1.5rem;line-height:1;}
.risk-gauge-center span{font-size:0.7rem;color:#8892a3;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.risk-list{padding:16px 20px 18px;}
.risk-line{display:flex;align-items:center;gap:11px;margin-bottom:12px;}
.r-ico{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.18);}
.r-ico.r-low{background:linear-gradient(135deg,#43e97b,#28a745);}
.r-ico.r-medium{background:linear-gradient(135deg,#ffb74d,#fd7e14);}
.r-ico.r-high{background:linear-gradient(135deg,#ff7a7a,#dc3545);}
.r-ico.r-critical{background:linear-gradient(135deg,#b34444,#7b1a1a);}
.risk-line .rname{min-width:58px;font-size:0.76rem;font-weight:700;text-align:right;}
.rname.r-low{color:#28a745;}
.rname.r-medium{color:#fd7e14;}
.rname.r-high{color:#dc3545;}
.rname.r-critical{color:#7b1a1a;}
.risk-bar{flex:1;height:12px;background:#eef1f4;border-radius:8px;overflow:hidden;}
.risk-bar-fill{height:100%;border-radius:8px;transition:width 0.7s ease;}
.risk-bar-fill.r-low{background:linear-gradient(90deg,#43e97b,#28a745);}
.risk-bar-fill.r-medium{background:linear-gradient(90deg,#ffb74d,#fd7e14);}
.risk-bar-fill.r-high{background:linear-gradient(90deg,#ff7a7a,#dc3545);}
.risk-bar-fill.r-critical{background:linear-gradient(90deg,#b34444,#7b1a1a);}
.risk-line.cur{background:#fbfdff;border:1.5px solid <?php echo $riskCur['color']; ?>;border-radius:10px;padding:7px 10px;margin-left:-10px;margin-right:-10px;box-shadow:0 3px 10px rgba(0,0,0,.06);}
.risk-count{font-weight:700;font-size:0.72rem;color:#5a6673;min-width:52px;text-align:center;padding:3px 9px;border-radius:20px;background:#eef1f4;}
.risk-line.cur .risk-count{background:<?php echo $riskCur['color']; ?>;color:#fff;}
.risk-cur-desc{margin-top:6px;padding:13px 15px;border-radius:10px;background:#f8fafc;font-size:0.82rem;color:#5a6673;line-height:1.55;border-left:4px solid <?php echo $riskCur['color']; ?>;}
.risk-cur-desc b{color:#333;}
@media(max-width:900px){.risk-row{grid-template-columns:1fr;}}

.stats-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:15px; margin-bottom:25px; }
.stat-card { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); display:flex; align-items:center; gap:15px; transition:transform 0.2s; }
.stat-card:hover { transform:translateY(-3px); }
.stat-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:white; }
.stat-icon.blue { background:linear-gradient(135deg,#0072C6,#00a3ff); }
.stat-icon.green { background:linear-gradient(135deg,#0072C6,#005999); }
.stat-icon.orange { background:linear-gradient(135deg,#fd7e14,#ffc107); }
.stat-icon.red { background:linear-gradient(135deg,#dc3545,#e74c3c); }
.stat-info h3 { font-size:1.5rem; color:#333; }
.stat-info p { color:#888; font-size:0.85rem; }

.card { background:white; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,0.06); margin-bottom:20px; overflow:hidden; }
.card-header { padding:16px 25px; border-bottom:1px solid #eef1f5; background:linear-gradient(90deg,#f8fbff,#eef4fb); display:flex; align-items:center; }
.card-header h2 { font-size:1.1rem; color:#12304a; display:flex; align-items:center; gap:10px; margin:0; }
.card-header h2 i { color:#fff; background:linear-gradient(135deg,#0a6cff,#00a3ff); width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.9rem; box-shadow:0 3px 8px rgba(10,108,255,0.35); }
.card-body { padding:20px 25px; }

.info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:14px; }
.info-item { background:linear-gradient(135deg,#fafcff,#f3f7fc); border:1px solid #e8eef5; border-radius:12px; padding:13px 16px; display:flex; align-items:center; gap:13px; transition:transform 0.2s, box-shadow 0.2s, border-color 0.2s; }
.info-item:hover { transform:translateY(-3px); box-shadow:0 8px 18px rgba(0,114,198,0.14); border-color:#c9ddf2; }
.info-item i { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; color:#fff; flex-shrink:0; box-shadow:0 4px 10px rgba(0,0,0,0.15); }
.info-item-body { min-width:0; }
.info-item-body label { display:block; font-size:0.7rem; color:#8a97a8; text-transform:uppercase; font-weight:700; letter-spacing:0.4px; margin-bottom:3px; }
.info-item-body span { font-size:0.92rem; color:#2b3444; font-weight:600; word-break:break-word; }

.contact-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:16px; }
.contact-box { background:white; border:1px solid #eef1f5; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:transform 0.2s, box-shadow 0.2s; }
.contact-box:hover { transform:translateY(-3px); box-shadow:0 10px 22px rgba(0,0,0,0.10); }
.cb-head { color:#fff; padding:14px 18px; font-size:0.95rem; font-weight:700; display:flex; align-items:center; gap:9px; }
.cb-head i { font-size:1rem; opacity:0.95; }
.cb-body { padding:6px 18px 10px; }
.cb-body p { font-size:0.85rem; color:#5b6574; margin:0; padding:9px 0; border-bottom:1px dashed #eef1f5; display:flex; justify-content:space-between; align-items:center; gap:10px; }
.cb-body p:last-child { border-bottom:none; }
.cb-body p strong { color:#2b3444; flex-shrink:0; }
.cb-body p span { color:#0a6cff; font-weight:700; text-align:right; word-break:break-all; }
.cb-body p span i { font-size:0.78rem; margin-right:5px; }

.cat-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.cat-tab { padding:9px 18px; border-radius:22px; border:1px solid #e0e6ee; background:white; cursor:pointer; font-weight:600; font-size:0.85rem; color:#55606e; transition:all 0.2s; display:flex; align-items:center; gap:7px; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
.cat-tab i { font-size:0.82rem; }
.cat-tab:hover { border-color:#0072C6; color:#0072C6; transform:translateY(-1px); }
.cat-tab.active { background:linear-gradient(90deg,#0072C6,#005999); color:white; border-color:transparent; box-shadow:0 4px 12px rgba(0,114,198,0.35); }

.chart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:25px;}
.chart-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.chart-card .card-header{padding:12px 18px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;}
.chart-wrap{position:relative;height:230px;padding:12px 14px 6px;}
.chart-empty{display:none;text-align:center;color:#a5afbd;padding:45px 10px;font-size:0.8rem;font-weight:600;}
.chart-empty i{display:block;font-size:2.2rem;color:#ccd5e0;margin-bottom:12px;}
</style>
</head>
<body>

<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div style="position:relative;">
                <div class="profile-area" onclick="toggleDropdown()">
                    <span style="font-weight:500;"><?php echo htmlspecialchars($barangay_name); ?></span>
                    <img src="<?php echo htmlspecialchars($header_logo ?? 'yana.png'); ?>" alt="Logo" onerror="this.src='yana.png'">
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="welcome-banner">
                <img class="wb-logo" src="<?php echo htmlspecialchars($header_logo ?? 'yana.png'); ?>" alt="Logo" onerror="this.src='yana.png'">
                <div class="wb-main">
                    <h2>Welcome, <?php echo htmlspecialchars($barangay_name); ?>!</h2>
                    <p>Municipality of Malilipot - Disaster Risk Reduction Management System</p>
                    <span class="risk-chip risk-<?php echo strtolower($details['risk_level'] ?? 'Low'); ?>"><i class="fas fa-exclamation-triangle"></i> Your Risk Level: <?php echo htmlspecialchars($details['risk_level'] ?? 'Low'); ?></span>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $reportCount; ?></h3>
                        <p>Total Reports</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-phone"></i></div>
                    <div class="stat-info">
                        <h3><?php echo !empty($contacts) ? 'Active' : 'None'; ?></h3>
                        <p>Emergency Contacts</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($details['population'] ?? 'N/A'); ?></h3>
                        <p>Population</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-home"></i></div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($details['households'] ?? 'N/A'); ?></h3>
                        <p>Households</p>
                    </div>
                </div>
            </div>

            <!-- RISK REPORT -->
            <div class="risk-row">
                <div class="risk-panel">
                    <div class="risk-head"><i class="fas fa-shield-halved" style="color:<?php echo $riskCur['color']; ?>;"></i> Risk Level Assessment</div>
                    <div class="risk-chart-wrap">
                        <canvas id="riskGauge"></canvas>
                        <div class="risk-gauge-center">
                            <div class="rg-ico" style="background:linear-gradient(135deg,<?php echo $riskCur['color']; ?>,<?php echo $riskCur['color']; ?>cc);"><i class="fas <?php echo $riskCur['icon']; ?>"></i></div>
                            <b style="color:<?php echo $riskCur['color']; ?>;"><?php echo htmlspecialchars($riskLevel); ?></b>
                            <span>Risk Level</span>
                        </div>
                    </div>
                    <div class="risk-cur-desc" style="margin:0 16px 16px;"><b><?php echo htmlspecialchars($riskLevel); ?> risk:</b> <?php echo htmlspecialchars($riskCur['desc']); ?></div>
                </div>
                <div class="risk-panel">
                    <div class="risk-head"><i class="fas fa-list-check" style="color:#0072C6;"></i> Risk Level Scale</div>
                    <div class="risk-list">
                        <?php foreach ($riskLevelsOrder as $lvl):
                            $pct = $riskMeta[$lvl]['pct'];
                            $isCur = $lvl === $riskLevel;
                        ?>
                        <div class="risk-line <?php echo $isCur ? 'cur' : ''; ?>">
                            <span class="r-ico r-<?php echo strtolower($lvl); ?>"><i class="fas <?php echo $riskMeta[$lvl]['icon']; ?>"></i></span>
                            <span class="rname r-<?php echo strtolower($lvl); ?>"><?php echo $lvl; ?></span>
                            <div class="risk-bar"><div class="risk-bar-fill r-<?php echo strtolower($lvl); ?>" style="width:<?php echo $pct; ?>%;"></div></div>
                            <span class="risk-count"><?php echo $isCur ? 'Current' : ''; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- VISUAL REPORTS -->
            <div class="chart-row">
                <div class="chart-card">
                    <div class="card-header"><i class="fas fa-users" style="color:#7c5cff;"></i> Population &amp; Households</div>
                    <div class="chart-wrap"><canvas id="popChart"></canvas></div>
                    <div class="chart-empty" id="popChartEmpty"><i class="fas fa-users"></i>No demographic data yet</div>
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
            </div>

            <!-- Barangay Info -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Barangay Information</h2>
                </div>
                <div class="card-body">
                    <div class="cat-tabs">
                        <div class="cat-tab active" onclick="showCat('identification',this)"><i class="fas fa-id-card"></i>Identification</div>
                        <div class="cat-tab" onclick="showCat('leadership',this)"><i class="fas fa-user-tie"></i>Leadership</div>
                        <div class="cat-tab" onclick="showCat('location',this)"><i class="fas fa-map-location-dot"></i>Location</div>
                        <div class="cat-tab" onclick="showCat('facilities',this)"><i class="fas fa-building"></i>Facilities</div>
                        <div class="cat-tab" onclick="showCat('other',this)"><i class="fas fa-ellipsis"></i>Other</div>
                    </div>

                    <?php foreach ($infoCats as $cat => $items): ?>
                    <div id="cat_<?php echo $cat; ?>" class="cat-section" <?php echo $cat === 'identification' ? '' : 'style="display:none;"'; ?>>
                        <div class="info-grid">
                            <?php foreach ($items as $it): ?>
                            <div class="info-item">
                                <i class="fas <?php echo $it[0]; ?>" style="background:linear-gradient(135deg,<?php echo $it[1]; ?>,<?php echo $it[1]; ?>b3);"></i>
                                <div class="info-item-body">
                                    <label><?php echo $it[2]; ?></label>
                                    <span><?php echo htmlspecialchars($it[3]); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Emergency Contacts -->
            <?php if (!empty($contacts)): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-phone-alt"></i> Emergency & Important Contacts</h2>
                </div>
                <div class="card-body">
                    <div class="contact-grid">
                        <?php foreach ($contactGroups as $grp): ?>
                        <div class="contact-box">
                            <div class="cb-head" style="background:<?php echo $grp[2]; ?>;"><i class="fas <?php echo $grp[1]; ?>"></i><?php echo $grp[0]; ?></div>
                            <div class="cb-body">
                                <?php foreach ($grp[3] as $row): ?>
                                <p><strong><?php echo $row[0]; ?></strong><span><i class="fas fa-phone-alt"></i><?php echo htmlspecialchars($row[1]); ?></span></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.profile-area')) document.getElementById("dropdownMenu").classList.remove("show"); }

function showCat(cat, el) {
    document.querySelectorAll('.cat-section').forEach(s => s.style.display='none');
    document.getElementById('cat_' + cat).style.display='block';
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

const BC = <?php echo json_encode($barangay_chart_json); ?>;
const B_COLORS = ['#7c5cff','#0072C6','#28a745','#fd7e14','#dc3545','#6c757d'];
function mountBarChart(canvasId, emptyId, labels, datasets, opts){
    const c = document.getElementById(canvasId);
    const has = datasets.some(ds => ds.data.some(v => v > 0));
    if (!has) { c.style.display = 'none'; document.getElementById(emptyId).style.display = 'block'; return; }
    new Chart(c, {
        type: 'doughnut',
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                tooltip: { callbacks: { label: (ctx) => {
                    const t = ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const p = t ? ((ctx.parsed / t) * 100).toFixed(1) : 0;
                    return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + p + '%)';
                } } }
            }
        }, opts || {})
    });
}
const demLbl = ['Population','Households','Head of Household'];
const demDat = [BC.population, BC.households, BC.head];
mountBarChart('popChart','popChartEmpty', demLbl, [{ data: demDat, backgroundColor: B_COLORS.slice(0,3), borderWidth: 2, borderColor: '#fff' }]);
const stMeta = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
const stLbl = [], stDat = [], stCol = [];
stMeta.forEach(m => { if (BC.status[m[0]] > 0) { stLbl.push(m[1]); stDat.push(BC.status[m[0]]); stCol.push(m[2]); } });
mountBarChart('statusChart','statusChartEmpty', stLbl, [{ data: stDat, backgroundColor: stCol, borderWidth: 2, borderColor: '#fff' }]);
mountBarChart('damageChart','damageChartEmpty', ['Totally','Partially'], [{ data: [BC.damage.Totally, BC.damage.Partially], backgroundColor: ['#dc3545','#fd7e14'], borderWidth: 2, borderColor: '#fff' }]);

/* Risk gauge */
const RISK_LEVEL = <?php echo json_encode($riskLevel); ?>;
const RISK_META = <?php echo json_encode(['Low'=>['pct'=>25,'color'=>'#28a745','track'=>'#e6f7eb'],'Medium'=>['pct'=>50,'color'=>'#fd7e14','track'=>'#fff1e0'],'High'=>['pct'=>75,'color'=>'#dc3545','track'=>'#fde8e8'],'Critical'=>['pct'=>100,'color'=>'#7b1a1a','track'=>'#f3e1e1']]); ?>;
(function initRiskGauge(){
    const el = document.getElementById('riskGauge');
    if (!el || typeof Chart === 'undefined') return;
    const m = RISK_META[RISK_LEVEL] || RISK_META.Low;
    new Chart(el, {
        type: 'doughnut',
        data: { labels: [RISK_LEVEL, 'Remaining'], datasets: [{ data: [m.pct, 100 - m.pct], backgroundColor: [m.color, m.track], borderWidth: 0, cutout: '76%' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });
})();
</script>

</body>
</html>
