<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="close-btn" onclick="closeSidebar()"><i class="fas fa-times"></i></span>
        <?php
        $header_logo = 'yana.png';
        if (isset($conn) && !empty($_SESSION['barangay_id'])) {
            $lq = $conn->query("SELECT logo FROM barangay_details WHERE barangay_id = " . intval($_SESSION['barangay_id']));
            if ($lq && ($lr = $lq->fetch_assoc()) && !empty($lr['logo'])) $header_logo = $lr['logo'];
        }
        ?>
        <div class="logo-ring">
            <img src="<?php echo htmlspecialchars($header_logo); ?>" alt="Logo" onerror="this.src='yana.png'">
        </div>
        <h3>Barangay Portal</h3>
        <p><i class="fas fa-map-marker-alt"></i> <?php echo strtoupper(htmlspecialchars($_SESSION['barangay_name'] ?? '')); ?></p>
        <?php
        $bRisk = 'Low';
        if (isset($conn)) {
            $bq = $conn->query("SELECT risk_level FROM barangay_details WHERE barangay_id = " . intval($_SESSION['barangay_id'] ?? 0));
            if ($bq && ($br = $bq->fetch_assoc())) $bRisk = $br['risk_level'] ?? 'Low';
        }
        ?>
        <div class="side-risk risk-<?php echo strtolower($bRisk); ?>"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($bRisk); ?> Risk</div>
    </div>

    <ul class="menu">
        <li>
            <a href="barangay_dashboard.php" class="sidebar-btn <?php echo $current_page === 'barangay_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>

        <li class="has-submenu <?php echo in_array($current_page, ['barangay_disaster_apply.php','barangay_disaster_approved.php','barangay_reedit.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleMenu(this)" class="sidebar-btn">
                <i class="fas fa-file-alt"></i> <span>Barangay Disaster Report</span>
            </a>
            <ul class="submenu">
                <li><a href="barangay_disaster_apply.php" class="sidebar-btn <?php echo $current_page === 'barangay_disaster_apply.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> <span>Apply</span></a></li>
                <li><a href="barangay_disaster_approved.php" class="sidebar-btn <?php echo $current_page === 'barangay_disaster_approved.php' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> <span>Barangay Report</span></a></li>
                <li><a href="barangay_reedit.php" class="sidebar-btn <?php echo $current_page === 'barangay_reedit.php' ? 'active' : ''; ?>"><i class="fas fa-redo"></i> <span>For Re-edit</span></a></li>
            </ul>
        </li>

        <li class="has-submenu <?php echo in_array($current_page, ['barangay_overview.php','barangay_info.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleMenu(this)" class="sidebar-btn">
                <i class="fas fa-info-circle"></i> <span>About Barangay</span>
            </a>
            <ul class="submenu">
                <li><a href="barangay_overview.php" class="sidebar-btn <?php echo $current_page === 'barangay_overview.php' ? 'active' : ''; ?>"><i class="fas fa-eye"></i> <span>Barangay Overview</span></a></li>
                <li><a href="barangay_info.php" class="sidebar-btn <?php echo $current_page === 'barangay_info.php' ? 'active' : ''; ?>"><i class="fas fa-building"></i> <span>Info &amp; Contacts</span></a></li>
            </ul>
        </li>

        <li class="has-submenu <?php echo in_array($current_page, ['barangay_hazard_map.php','barangay_hazard_map_view.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)" onclick="toggleMenu(this)" class="sidebar-btn">
                <i class="fas fa-map-marked-alt"></i> <span>Hazard Map</span>
            </a>
            <ul class="submenu">
                <li><a href="barangay_hazard_map.php" class="sidebar-btn <?php echo $current_page === 'barangay_hazard_map.php' ? 'active' : ''; ?>"><i class="fas fa-edit"></i> <span>Edit Hazard Map</span></a></li>
                <li><a href="barangay_hazard_map_view.php" class="sidebar-btn <?php echo $current_page === 'barangay_hazard_map_view.php' ? 'active' : ''; ?>"><i class="fas fa-eye"></i> <span>View Hazard Map</span></a></li>
            </ul>
        </li>

        <li>
            <a href="barangay_message.php" class="sidebar-btn <?php echo $current_page === 'barangay_message.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> <span>Messages</span>
            </a>
        </li>

        <li>
            <a href="barangay_announcements.php" class="sidebar-btn <?php echo $current_page === 'barangay_announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a>
        </li>

        <li>
            <a href="barangay_relief_distribution.php" class="sidebar-btn <?php echo $current_page === 'barangay_relief_distribution.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i> <span>Relief Goods Distribution</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <i class="fas fa-map-marker-alt"></i> Malilipot, Albay
    </div>
</div>

<button class="sidebar-edge" onclick="openSidebar()" title="Show menu"><i class="fas fa-bars"></i></button>

<style>
/* SIDEBAR REOPEN TAB */
.sidebar-edge {
    display: none;
    position: fixed;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1001;
    width: 40px;
    height: 72px;
    border: none;
    border-radius: 0 16px 16px 0;
    background: linear-gradient(180deg, #0a6cff, #004a9f);
    color: #fff;
    font-size: 17px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    box-shadow: 3px 0 12px rgba(0,0,0,.3);
    transition: background 0.3s;
}
.sidebar.hide + .sidebar-edge {
    display: flex;
    animation: edgeIn .35s cubic-bezier(0.25, 0.8, 0.25, 1);
}
@keyframes edgeIn {
    from { opacity: 0; transform: translateY(-50%) translateX(-12px); }
    to   { opacity: 1; transform: translateY(-50%) translateX(0); }
}
.sidebar-edge:hover {
    background: #0a5ed7;
}

/* Main content adjusts with sidebar visibility */
.sidebar:not(.hide) ~ .main-content {
    margin-left: 260px;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100%;
    background: linear-gradient(180deg, #0a6cff 0%, #0056c7 55%, #003d8f 100%);
    color: #fff;
    transform: translateX(0);
    transition: transform 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
    will-change: transform;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 4px 0 20px rgba(0,60,150,.35);
    z-index: 1000;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.sidebar::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -60px;
    width: 210px;
    height: 210px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.sidebar::after {
    content: '';
    position: absolute;
    top: 160px;
    left: -70px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}
.sidebar.hide {
    transform: translateX(-100%);
}

/* HEADER */
.sidebar-header {
    position: relative;
    text-align: center;
    padding: 30px 20px 22px;
    border-bottom: 1px solid rgba(255,255,255,.15);
    background: rgba(0,0,0,.12);
}
.logo-ring {
    width: 82px;
    height: 82px;
    margin: 0 auto 12px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255,255,255,.35), rgba(255,255,255,.05));
    padding: 5px;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
}
.logo-ring img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    background: #fff;
}
.sidebar-header h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: .3px;
}
.sidebar-header p {
    margin: 6px 0 0;
    font-size: .8rem;
    opacity: .85;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.side-risk {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 12px auto 0;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: .74rem;
    font-weight: 700;
    letter-spacing: .3px;
    width: fit-content;
}
.side-risk.risk-low{background:rgba(40,167,69,.22);color:#b8f5c9;border:1px solid rgba(74,222,128,.55);}
.side-risk.risk-medium{background:rgba(253,126,20,.22);color:#ffdcae;border:1px solid rgba(251,191,36,.55);}
.side-risk.risk-high{background:rgba(220,53,69,.24);color:#ffc3c9;border:1px solid rgba(248,113,113,.55);}
.side-risk.risk-critical{background:rgba(123,26,26,.45);color:#f8b5b5;border:1px solid rgba(255,90,90,.6);animation:sidePulse 1.6s infinite;}
@keyframes sidePulse{0%,100%{opacity:1;}50%{opacity:.55;}}
.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,.2);
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    transition: background .2s, transform .25s;
}
.close-btn:hover {
    background: rgba(0,0,0,.4);
    transform: rotate(90deg);
}

/* MENU */
.menu, .submenu {
    list-style: none;
    padding: 0;
    margin: 0;
}
.menu {
    position: relative;
    z-index: 1;
    padding: 14px 12px 10px;
}
.sidebar-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 4px;
    color: rgba(255,255,255,.92);
    text-decoration: none;
    border-radius: 12px;
    font-size: .92rem;
    font-weight: 500;
    transition: background .2s, color .2s, transform .15s;
}
.sidebar-btn > i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
    color: rgba(255,255,255,.75);
    transition: color .2s;
}
.sidebar-btn:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
    transform: translateX(3px);
}
.sidebar-btn:hover > i {
    color: #ffd700;
}
.sidebar-btn.active {
    background: #fff;
    color: #003d8f;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0,0,0,.18);
}
.sidebar-btn.active > i {
    color: #0a6cff;
}

/* SUBMENU */
.has-submenu > .sidebar-btn::after {
    content: '\f105';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    margin-left: auto;
    font-size: .8rem;
    color: rgba(255,255,255,.7);
    transition: transform .25s;
}
.has-submenu.active > .sidebar-btn::after {
    transform: rotate(90deg);
}
.submenu {
    display: none;
    margin: 2px 0 6px 14px;
    padding-left: 12px;
    border-left: 2px solid rgba(255,255,255,.25);
}
.menu .active > .submenu {
    display: block;
}
.submenu .sidebar-btn {
    padding: 10px 12px;
    font-size: .87rem;
    margin-bottom: 2px;
}
.submenu .sidebar-btn > i {
    font-size: .85rem;
}

/* FOOTER */
.sidebar-footer {
    position: relative;
    z-index: 1;
    margin: 10px 12px 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(0,0,0,.18);
    font-size: .78rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,.85);
}

/* SCROLLBAR */
.sidebar::-webkit-scrollbar {
    width: 5px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.3);
    border-radius: 4px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        max-width: 300px;
    }
    .sidebar.hide {
        transform: translateX(-100%);
    }
    .sidebar:not(.hide) ~ .main-content {
        margin-left: 0;
    }
}
</style>

<script>
function openSidebar() {
    document.getElementById("sidebar").classList.remove("hide");
}

function closeSidebar() {
    document.getElementById("sidebar").classList.add("hide");
}

// Toggle submenu open/close (only one open)
function toggleMenu(element) {
    const isActive = element.parentElement.classList.contains('active');
    document.querySelectorAll('.has-submenu').forEach(item => item.classList.remove('active'));
    if (!isActive) element.parentElement.classList.add('active');
}

// Collapse submenu when clicking item
function closeSubmenu(link) {
    const parent = link.closest('.has-submenu');
    if (parent) parent.classList.remove('active');
}
</script>
