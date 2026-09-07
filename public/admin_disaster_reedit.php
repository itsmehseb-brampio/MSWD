<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

$search = trim($_GET['search'] ?? '');
$sql = "SELECT dr.*, b.barangay_name FROM disaster_reports dr LEFT JOIN barangays b ON dr.barangay_id = b.barangay_id WHERE dr.status='reedit'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $sql .= " AND (dr.format_no LIKE '%$s%' OR dr.household_head LIKE '%$s%' OR dr.disaster_type LIKE '%$s%' OR b.barangay_name LIKE '%$s%')";
}
$sql .= " ORDER BY dr.created_at DESC";
$result = $conn->query($sql);
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$dchart_scope = 'admin';
$dchart_where = "status='reedit'";
include 'disaster_charts_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Re-edit Reports - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.4rem;}
.admin-profile{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.25);}
.admin-profile img{width:36px;height:36px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}
.tabs-bar{display:flex;background:#e8ecf0;padding:0;min-height:40px;align-items:flex-end;overflow-x:auto;border-bottom:2px solid #ddd;}
.tab,.tab-home{padding:10px 18px;border-radius:8px 8px 0 0;cursor:pointer;font-size:0.85rem;font-weight:500;white-space:nowrap;margin-right:2px;transition:background 0.2s;display:flex;align-items:center;gap:8px;text-decoration:none;}
.tab{background:#dde2e8;color:#555;}
.tab:hover{background:#cdd3da;}
.tab.active{background:#f5f7fa;color:#6f42c1;font-weight:700;border:2px solid #ddd;border-bottom:2px solid #f5f7fa;margin-bottom:-2px;}
.tab .close-tab{margin-left:8px;width:18px;height:18px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:11px;color:#888;transition:all 0.2s;}
.tab .close-tab:hover{background:#dc3545;color:white;}
.tab-home{background:#0072C6;color:white;font-weight:600;}
.search-row{display:flex;align-items:center;gap:10px;padding:12px 25px;background:white;border-bottom:1px solid #e8e8e8;}
.search-wrapper{position:relative;flex:1;max-width:400px;}
.search-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;font-size:0.9rem;}
.search-wrapper input{width:100%;padding:9px 14px 9px 38px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.9rem;transition:border-color 0.3s;}
.search-wrapper input:focus{outline:none;border-color:#6f42c1;}
.search-clear{background:none;border:none;color:#999;cursor:pointer;font-size:1.1rem;padding:4px 8px;border-radius:50%;}
.search-clear:hover{background:#f0f0f0;color:#333;}
.content{padding:25px 30px;}
.tab-content{display:none;}
.tab-content.active{display:block;}
.page-card{background:white;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,0.08);overflow:hidden;}
.page-card h2{color:#6f42c1;padding:18px 25px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:10px;font-size:1.1rem;}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px 15px;text-align:left;border-bottom:1px solid #f0f0f0;font-size:0.88rem;}
th{color:#666;font-weight:600;font-size:0.82rem;text-transform:uppercase;background:#fafafa;}
tr:hover{background:#f9f9f9;}
tr td:first-child{font-weight:600;color:#333;}
.badge{padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;background:#e8daef;color:#6f42c1;}
.empty-msg{text-align:center;color:#999;padding:50px;}
.empty-msg i{font-size:2.5rem;margin-bottom:10px;display:block;color:#ddd;}
.action-cell{display:flex;gap:6px;}
.btn-action{padding:5px 12px;border-radius:6px;font-size:0.8rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all 0.2s;}
.btn-view{cursor:pointer;background:#e3f2fd;color:#1565c0;}
.btn-view:hover{background:#bbdefb;}

.bond-paper{width:100%;max-width:210mm;margin:0 auto;background:white;padding:40px 50px 35px;box-shadow:0 4px 20px rgba(0,0,0,0.15);}
.bond-header{text-align:center;border-bottom:3px double #333;padding-bottom:20px;margin-bottom:25px;}
.bond-header .repub{font-size:11px;text-transform:uppercase;letter-spacing:2px;color:#555;}
.bond-header h1{font-size:18px;text-transform:uppercase;margin:5px 0;letter-spacing:1px;}
.bond-header h2{font-size:14px;font-weight:400;margin:3px 0;}
.bond-header .dept{font-size:11px;color:#666;}
.bond-title-bar{background:#f5f5f5;border:1px solid #ddd;padding:12px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;border-radius:4px;}
.bond-title-bar .ref{font-size:13px;color:#555;}
.bond-title-bar .ref strong{color:#333;}
.bond-title-bar .sbadge{padding:4px 14px;border-radius:12px;font-size:11px;font-weight:700;}
.sbadge-green{background:#d4edda;color:#155724;}
.sbadge-red{background:#f8d7da;color:#721c24;}
.sbadge-orange{background:#fff3cd;color:#856404;}
.sbadge-purple{background:#e8daef;color:#6f42c1;}
.bond-table{width:100%;border-collapse:collapse;margin-bottom:20px;}
.bond-table td{padding:10px 12px;border:1px solid #ccc;font-size:13px;vertical-align:top;}
.bond-table td:first-child{font-weight:600;width:190px;color:#444;background:#fafafa;}
.bond-desc{border:1px solid #ccc;border-radius:4px;margin-bottom:20px;}
.bond-desc h4{padding:8px 12px;background:#f5f5f5;border-bottom:1px solid #ccc;font-size:12px;text-transform:uppercase;color:#555;}
.bond-desc p{padding:12px;font-size:13px;color:#333;min-height:40px;line-height:1.5;}
.bond-sig{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px;padding:0 20px;}
.bond-sig-box{text-align:center;}
.bond-sig-line{width:80%;height:1px;background:#333;margin:0 auto 8px;padding-top:40px;}
.bond-sig-label{font-size:12px;font-weight:700;color:#333;text-transform:uppercase;}
.bond-sig-sub{font-size:10px;color:#888;margin-top:3px;}
.bond-annex{text-align:center;border-bottom:1px solid #ddd;padding-bottom:12px;margin-bottom:20px;}
.bond-annex .atitle{font-size:11px;color:#555;text-transform:uppercase;letter-spacing:1px;}
.bond-annex h2{font-size:16px;margin-top:3px;}
.bond-annex .asub{font-size:11px;color:#888;margin-top:3px;}
.bond-pg2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:10px;}
.bond-pg2-item{text-align:center;}
.bond-pg2-item img{width:100%;border:1px solid #ddd;border-radius:4px;}
.bond-pg2-item .pl{font-size:11px;color:#666;margin-top:6px;font-weight:600;}
.bond-cert{text-align:center;margin-top:25px;font-size:11px;color:#888;font-style:italic;}
.bond-b2b{text-align:center;margin-top:15px;}
.bond-b2b img{max-width:280px;border:1px solid #ddd;border-radius:4px;}
.bond-page-label{text-align:right;font-size:11px;color:#999;margin-top:30px;}
.bond-sep{height:2px;background:#e0e0e0;margin:30px 0;}
.status-nav{display:flex;gap:8px;padding:10px 25px;background:white;border-bottom:1px solid #e8e8e8;}
.status-btn{padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.2s;border:2px solid transparent;}
.status-approved{background:#d4edda;color:#155724;}
.status-approved:hover,.status-approved.active{background:#28a745;color:white;}
.status-declined{background:#f8d7da;color:#721c24;}
.status-declined:hover,.status-declined.active{background:#dc3545;color:white;}
.status-processing{background:#fff3cd;color:#856404;}
.status-processing:hover,.status-processing.active{background:#fd7e14;color:white;}
.status-reedit{background:#e8daef;color:#6f42c1;}
.status-reedit:hover,.status-reedit.active{background:#6f42c1;color:white;}
.status-history{background:#d1ecf1;color:#0c5460;}
.status-history:hover,.status-history.active{background:#0072C6;color:white;}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-redo"></i> Re-edit Reports</h1>
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
            <div class="tab-home active" id="tabListHome" onclick="showListView()"><i class="fas fa-list"></i> Report List</div>
        </div>

        <div class="status-nav">
            <a href="admin_disaster_approved.php" class="status-btn status-approved"><i class="fas fa-check-circle"></i> Approved</a>
            <a href="admin_disaster_declined.php" class="status-btn status-declined"><i class="fas fa-times-circle"></i> Declined</a>
            <a href="admin_disaster_pending.php" class="status-btn status-processing"><i class="fas fa-clock"></i> Processing</a>
            <a href="admin_disaster_reedit.php" class="status-btn status-reedit active"><i class="fas fa-redo"></i> Re-edit</a>
            <a href="admin_disaster_history.php" class="status-btn status-history"><i class="fas fa-history"></i> History</a>
        </div>

        <div class="tab-content active" id="viewList">
            <div class="search-row">
                <form method="GET" style="display:flex;align-items:center;gap:10px;width:100%;">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by format, barangay, household..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" style="padding:9px 16px;background:#6f42c1;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;"><i class="fas fa-search"></i></button>
                    <?php if ($search): ?>
                    <a href="admin_disaster_reedit.php" class="search-clear"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="content">
                <?php include 'disaster_charts_block.php'; ?>
                <div class="page-card">
                    <h2><i class="fas fa-redo"></i> Reports Sent Back for Re-edit <span style="margin-left:auto;font-size:0.8rem;color:#999;"><?php echo count($reports); ?> result(s)</span></h2>
                    <?php if (count($reports) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Format No.</th><th>Barangay</th><th>Household Name</th><th>Disaster Type</th><th>Damage</th><th>Date</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['format_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['barangay_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['household_head'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['disaster_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['damage_extent'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'] ?? 'now')); ?></td>
                                <td><span class="badge">For Re-edit</span></td>
                                <td>
                                    <div class="action-cell">
                                        <button class="btn-action btn-view" data-report='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>' onclick='openReportTab(JSON.parse(this.dataset.report))'><i class="fas fa-eye"></i> View</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-msg"><i class="fas fa-inbox"></i><p>No reports are currently being re-edited by barangays.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php foreach ($reports as $row): ?>
        <div class="tab-content" id="reportView_<?php echo $row['report_id']; ?>"></div>
        <?php endforeach; ?>
    </div>
</div>

<script>
let openReportTabs = {};

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.admin-profile'))document.getElementById("dropdownMenu").classList.remove("show");}

function showListView() {
    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    document.getElementById('viewList').classList.add('active');
    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));
    document.getElementById('tabListHome').classList.add('active');
}

function makeBondHTML(r) {
    const statusClass = 'sbadge-purple';
    const base = '../barangay/uploads/';
    const cap = r.captain_name || 'Punong Barangay';
    const sec = r.secretary_name || 'Barangay Secretary';
    const declineReason = r.decline_reason ? '<div style="background:#fef3e2;border-left:4px solid #fd7e14;padding:12px 16px;margin-bottom:20px;border-radius:0 6px 6px 0;"><strong style="color:#856404;"><i class="fas fa-exclamation-circle"></i> Instructions for Revision:</strong><p style="color:#856404;margin-top:5px;font-size:13px;">' + esc(r.decline_reason) + '</p></div>' : '';

    return `<div class="bond-paper">
        <div style="text-align:right;margin-bottom:10px;">
            <button onclick="window.print()" style="padding:8px 16px;background:#0072C6;color:white;border:none;border-radius:6px;font-weight:600;font-size:0.85rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"><i class="fas fa-print"></i> Print</button>
        </div>

        <div class="bond-header">
            <div class="repub">Republic of the Philippines</div>
            <h1>Province of Albay</h1>
            <h2>Municipality of Malilipot</h2>
            <div class="dept">Disaster Risk Reduction Management Office</div>
        </div>

        <div class="bond-title-bar">
            <div class="ref"><strong>Report No.:</strong> ${r.format_no || 'N/A'}</div>
            <div class="ref"><strong>Barangay:</strong> ${r.barangay_name || 'N/A'}</div>
            <span class="sbadge ${statusClass}">FOR RE-EDIT</span>
        </div>

        ${declineReason}

        <table class="bond-table">
            <tr><td>Report Title</td><td>${esc(r.title)}</td></tr>
            <tr><td>Type of Disaster</td><td>${esc(r.disaster_type)}</td></tr>
            <tr><td>Name of Household Head</td><td>${esc(r.household_head)}</td></tr>
            <tr><td>Number of Family Members</td><td>${esc(r.family_members)}</td></tr>
            <tr><td>Full Address</td><td>${esc(r.full_address)}</td></tr>
            <tr><td>Housing Type</td><td>${esc(r.housing_type)}</td></tr>
            <tr><td>Extent of Damage</td><td>${esc(r.damage_extent)}</td></tr>
            <tr><td>Date Reported</td><td>${new Date(r.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</td></tr>
        </table>

        <div class="bond-desc">
            <h4>Description / Remarks</h4>
            <p>${esc(r.description) || 'No remarks provided.'}</p>
        </div>

        <div class="bond-sig">
            <div class="bond-sig-box"><div class="bond-sig-line"></div><div class="bond-sig-label">${esc(cap)}</div><div class="bond-sig-sub">Punong Barangay</div></div>
            <div class="bond-sig-box"><div class="bond-sig-line"></div><div class="bond-sig-label">${esc(sec)}</div><div class="bond-sig-sub">Barangay Secretary</div></div>
        </div>
        <div class="bond-page-label">Page 1 of 3</div>
        <div class="bond-sep"></div>
        <div class="bond-annex"><div class="atitle">Annex A</div><h2>Photographs of Damage</h2><div class="asub">${esc(r.format_no)} — ${esc(r.barangay_name)}</div></div>
        <div class="bond-pg2">
            ${[1,2,3,4].map(i => { const p = r['pic'+i]; const src = p ? (base + p) : ''; return `<div class="bond-pg2-item">${src ? '<img src="'+src+'">' : '<div style="border:1px dashed #ccc;border-radius:4px;padding:40px 20px;color:#ccc;font-size:12px;">No Photo</div>'}<div class="pl">Photo ${i}</div></div>`; }).join('')}
        </div>
        <div class="bond-cert">I hereby certify that the above photographs are true and accurate representations of the damage caused by the disaster.</div>
        <div class="bond-page-label">Page 2 of 3</div>
        <div class="bond-sep"></div>
        <div class="bond-annex"><div class="atitle">Annex B</div><h2>B2B ID / Valid Identification</h2><div class="asub">${esc(r.format_no)} — ${esc(r.barangay_name)}</div></div>
        <div class="bond-b2b">${(() => { const s = r.b2b_id ? (base + r.b2b_id) : ''; return s ? '<img src="'+s+'">' : '<div style="border:2px dashed #ccc;border-radius:8px;padding:60px 40px;display:inline-block;color:#ccc;font-size:14px;"><i class="fas fa-id-card" style="font-size:40px;display:block;margin-bottom:10px;"></i>No B2B ID uploaded</div>'; })()}</div>
        <div style="text-align:center;margin-top:25px;font-size:11px;color:#888;font-style:italic;">This document serves as the official disaster report submitted to the Municipal DSWD Office.</div>
        <div class="bond-sig" style="margin-top:40px;">
            <div class="bond-sig-box"><div class="bond-sig-line"></div><div class="bond-sig-label">${esc(cap)}</div><div class="bond-sig-sub">Punong Barangay</div></div>
            <div class="bond-sig-box"><div class="bond-sig-line"></div><div class="bond-sig-label">${esc(sec)}</div><div class="bond-sig-sub">Barangay Secretary</div></div>
        </div>
        <div class="bond-page-label">Page 3 of 3</div>
    </div>`;
}

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : 'N/A'; }

function openReportTab(r) {
    const id = r.report_id;
    const name = r.household_head || r.format_no || 'Report';
    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    let view = document.getElementById('reportView_' + id);
    if (!view) {
        view = document.createElement('div');
        view.className = 'tab-content';
        view.id = 'reportView_' + id;
        document.querySelector('.main-content').appendChild(view);
    }
    view.innerHTML = '<div style="padding:25px 30px;">' + makeBondHTML(r) + '</div>';
    view.classList.add('active');
    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));
    if (!openReportTabs[id]) {
        const tab = document.createElement('div');
        tab.className = 'tab active';
        tab.id = 'tab_report_' + id;
        tab.innerHTML = '<i class="fas fa-file-alt"></i> ' + name + ' <span class="close-tab" onclick="event.stopPropagation(); closeReportTab(' + id + ')">&times;</span>';
        tab.onclick = function() { openReportTab(r); };
        document.getElementById('tabsBar').appendChild(tab);
        openReportTabs[id] = true;
    } else {
        document.getElementById('tab_report_' + id).classList.add('active');
    }
    document.getElementById('tabListHome').classList.remove('active');
}

function closeReportTab(id) {
    const tab = document.getElementById('tab_report_' + id);
    if (tab) tab.remove();
    delete openReportTabs[id];
    const view = document.getElementById('reportView_' + id);
    if (view) { view.classList.remove('active'); view.remove(); }
    const remaining = document.querySelectorAll('.tab');
    if (remaining.length > 0) { remaining[remaining.length - 1].click(); } else { showListView(); }
}
</script>
</body>
</html>