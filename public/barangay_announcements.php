<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';
$barangay_name = $_SESSION['barangay_name'] ?? 'Barangay';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - <?php echo htmlspecialchars($barangay_name); ?></title>
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
.profile-area{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.profile-area:hover{background:rgba(255,255,255,0.25);}
.profile-area img{width:36px;height:36px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.two-col{padding:24px 30px;}
.col-left{display:flex;flex-direction:column;gap:20px;}

.panel{background:white;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;}
.panel-header{padding:14px 20px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:8px;font-weight:700;font-size:0.95rem;color:#333;}
.panel-body{padding:16px 20px;}

.featured-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.featured-card{border-radius:10px;padding:18px;border:1px solid #e8e8e8;position:relative;overflow:hidden;transition:box-shadow 0.2s;}
.featured-card:hover{box-shadow:0 4px 15px rgba(0,0,0,0.1);}
.featured-card.pinned{background:linear-gradient(135deg,#fff5f5,#fff);border:1px solid #f5c6cb;border-left:4px solid #dc3545;}
.featured-card.recent{background:linear-gradient(135deg,#f0f8ff,#fff);border:1px solid #bee5eb;border-left:4px solid #0072C6;}
.featured-label{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
.featured-label.pin{color:#dc3545;}
.featured-label.rec{color:#0072C6;}
.featured-title{font-weight:700;font-size:1rem;color:#333;margin-bottom:6px;}
.featured-msg{font-size:0.85rem;color:#555;line-height:1.5;white-space:pre-wrap;word-wrap:break-word;}
.featured-meta{font-size:0.75rem;color:#aaa;margin-top:10px;}
.featured-empty{grid-column:1/-1;text-align:center;padding:20px;color:#bbb;font-size:0.85rem;}

.announce-list{display:flex;flex-direction:column;gap:10px;max-height:500px;overflow-y:auto;}
.announce-card{border:1px solid #e8e8e8;border-radius:10px;padding:14px 18px;transition:box-shadow 0.2s;}
.announce-card:hover{box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.announce-card.pinned{border-left:3px solid #dc3545;background:#fff8f8;}
.announce-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;}
.announce-title{font-weight:700;font-size:0.92rem;color:#333;}
.badge-pin{background:#dc3545;color:white;padding:2px 8px;border-radius:10px;font-size:0.66rem;font-weight:600;}
.announce-msg{font-size:0.84rem;color:#555;line-height:1.5;white-space:pre-wrap;margin-bottom:8px;}
.announce-meta{font-size:0.75rem;color:#aaa;}

.empty{text-align:center;padding:30px;color:#bbb;}
.empty i{font-size:2rem;margin-bottom:8px;display:block;}

@media(max-width:1100px){.two-col{grid-template-columns:1fr;}.featured-grid{grid-template-columns:1fr;}}
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
                    <img src="<?php echo htmlspecialchars($header_logo ?? 'yana.png'); ?>" alt="Logo" onerror="this.src='yana.png'">
                    <span><?php echo htmlspecialchars($barangay_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="two-col">
            <!-- LEFT: Announcements -->
            <div class="col-left">
                <!-- Featured: Pinned + Recent -->
                <div class="panel">
                    <div class="panel-header"><i class="fas fa-star" style="color:#ffc107;"></i> Highlights</div>
                    <div class="panel-body">
                        <div class="featured-grid" id="featuredGrid">
                            <div class="featured-empty"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                    </div>
                </div>

                <!-- All Announcements -->
                <div class="panel">
                    <div class="panel-header"><i class="fas fa-bullhorn" style="color:#dc3545;"></i> All Announcements</div>
                    <div class="panel-body">
                        <div class="announce-list" id="announceList">
                            <div class="empty"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.profile-area'))document.getElementById("dropdownMenu").classList.remove("show");}

let allAnnouncements = [];

function loadAnnouncements(){
    fetch('announcement_api.php?action=list').then(r=>r.json()).then(data=>{
        allAnnouncements = data;
        renderFeatured(data);
        renderList(data);
    }).catch(()=>{});
}

function renderFeatured(items){
    const el = document.getElementById('featuredGrid');
    const pinned = items.find(a => a.is_pinned == 1);
    const recent = items.find(a => a.is_pinned != 1) || items[1];

    if(!pinned && !recent){
        el.innerHTML = '<div class="featured-empty">No announcements yet</div>';
        return;
    }

    let html = '';
    if(pinned){
        const t = new Date(pinned.created_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
        html += '<div class="featured-card pinned">'+
            '<div class="featured-label pin"><i class="fas fa-thumbtack"></i> Pinned</div>'+
            '<div class="featured-title">'+esc(pinned.title)+'</div>'+
            '<div class="featured-msg">'+esc(pinned.message)+'</div>'+
            '<div class="featured-meta"><i class="fas fa-user-shield"></i> '+esc(pinned.admin_name||'Admin')+' · '+t+'</div></div>';
    }
    if(recent && recent !== pinned){
        const t = new Date(recent.created_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
        html += '<div class="featured-card recent">'+
            '<div class="featured-label rec"><i class="fas fa-clock"></i> Latest</div>'+
            '<div class="featured-title">'+esc(recent.title)+'</div>'+
            '<div class="featured-msg">'+esc(recent.message)+'</div>'+
            '<div class="featured-meta"><i class="fas fa-user-shield"></i> '+esc(recent.admin_name||'Admin')+' · '+t+'</div></div>';
    }
    if(!pinned && recent){
        html += '<div class="featured-empty">No pinned announcement</div>';
    }
    if(pinned && !recent){
        html += '<div class="featured-empty">No other announcements</div>';
    }
    el.innerHTML = html;
}

function renderList(items){
    const el = document.getElementById('announceList');
    if(!items.length){el.innerHTML='<div class="empty"><i class="fas fa-bullhorn"></i><p>No announcements yet</p></div>';return;}
    el.innerHTML = items.map(a=>{
        const t = new Date(a.created_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
        const pin = a.is_pinned==1?'<span class="badge-pin"><i class="fas fa-thumbtack"></i> Pinned</span>':'';
        return '<div class="announce-card '+(a.is_pinned==1?'pinned':'')+'">'+
            '<div class="announce-top"><div class="announce-title">'+esc(a.title)+'</div><div>'+pin+'</div></div>'+
            '<div class="announce-msg">'+esc(a.message)+'</div>'+
            '<div class="announce-meta"><i class="fas fa-user-shield"></i> '+esc(a.admin_name||'Admin')+' · '+t+'</div></div>';
    }).join('');
}

function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):'';}

loadAnnouncements();
setInterval(loadAnnouncements,15000);
</script>
</body>
</html>
