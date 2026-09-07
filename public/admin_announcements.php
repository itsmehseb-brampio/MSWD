<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';
$brgy_result = $conn->query("SELECT b.barangay_id, b.barangay_name, COALESCE(bd.households,0) as households FROM barangays b LEFT JOIN barangay_details bd ON b.barangay_id=bd.barangay_id ORDER BY b.barangay_name ASC");
$barangays = [];
while ($r = $brgy_result->fetch_assoc()) { $barangays[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Admin Panel</title>
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

.two-col{padding:24px 30px;min-height:calc(100vh - 70px);}
.col-left{display:flex;flex-direction:column;gap:20px;}

.panel{background:white;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;}
.panel-header{padding:16px 20px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:8px;font-weight:700;font-size:1rem;color:#333;}
.panel-body{padding:20px;}

.announce-form{display:flex;flex-direction:column;gap:12px;}
.form-group label{display:block;font-size:0.8rem;font-weight:600;color:#555;margin-bottom:5px;}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:9px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;font-family:inherit;}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:#0072C6;}
.form-group textarea{height:90px;resize:vertical;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.form-actions{display:flex;gap:10px;align-items:center;margin-top:4px;}
.btn-post{padding:9px 20px;background:#0072C6;color:white;border:none;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;}
.btn-post:hover{background:#005999;}
.pin-check{display:flex;align-items:center;gap:6px;font-size:0.82rem;color:#555;cursor:pointer;}
.pin-check input{accent-color:#dc3545;width:15px;height:15px;}

.target-section{margin-top:2px;}
.target-all{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0f8ff;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:0.82rem;}
.target-all.active{border-color:#0072C6;background:#e3f2fd;}
.target-all input{accent-color:#0072C6;width:15px;height:15px;}
.target-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;max-height:120px;overflow-y:auto;padding:4px 0;display:none;}
.target-item{display:flex;align-items:center;gap:6px;padding:6px 10px;background:#f9f9f9;border:1px solid #e8e8e8;border-radius:6px;cursor:pointer;font-size:0.8rem;}
.target-item.selected{background:#e3f2fd;border-color:#0072C6;}
.target-item input{accent-color:#0072C6;width:14px;height:14px;}

.announce-list{display:flex;flex-direction:column;gap:10px;}
.announce-card{border:1px solid #e8e8e8;border-radius:10px;padding:14px 18px;transition:box-shadow 0.2s;}
.announce-card:hover{box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.announce-card.pinned{border-left:3px solid #dc3545;background:#fff8f8;}
.announce-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;}
.announce-title{font-weight:700;font-size:0.95rem;color:#333;}
.announce-badges{display:flex;gap:5px;flex-wrap:wrap;flex-shrink:0;}
.badge-pin{background:#dc3545;color:white;padding:3px 8px;border-radius:10px;font-size:0.68rem;font-weight:600;}
.badge-target{background:#e8daef;color:#6f42c1;padding:3px 8px;border-radius:10px;font-size:0.68rem;font-weight:600;}
.badge-all{background:#d4edda;color:#155724;padding:3px 8px;border-radius:10px;font-size:0.68rem;font-weight:600;}
.announce-msg{font-size:0.85rem;color:#555;line-height:1.5;white-space:pre-wrap;margin-bottom:8px;}
.announce-meta{display:flex;justify-content:space-between;align-items:center;font-size:0.75rem;color:#aaa;}
.announce-actions{display:flex;gap:5px;}
.announce-actions button{padding:4px 8px;border-radius:5px;border:none;font-size:0.72rem;font-weight:600;cursor:pointer;}
.btn-pin{background:#fff3cd;color:#856404;}
.btn-unpin{background:#f8f9fa;color:#666;}
.btn-delete{background:#f8d7da;color:#721c24;}
.btn-delete:hover{background:#f5c6cb;}

.empty{text-align:center;padding:30px;color:#bbb;}
.empty i{font-size:2.5rem;margin-bottom:8px;display:block;}

@media(max-width:1100px){.two-col{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
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

        <div class="two-col">
            <!-- LEFT: Announcements -->
            <div class="col-left">
                <div class="panel">
                    <div class="panel-header"><i class="fas fa-bullhorn" style="color:#dc3545;"></i> Announcements</div>
                    <div class="panel-body">
                        <div class="announce-form">
                            <div class="form-group">
                                <input type="text" id="annTitle" placeholder="Title...">
                            </div>
                            <div class="form-group">
                                <textarea id="annMsg" placeholder="Write announcement..."></textarea>
                            </div>
                            <div class="target-section">
                                <div class="target-all active" id="annTargetAllWrap" onclick="toggleAnnTarget()">
                                    <input type="checkbox" id="annTargetAll" checked>
                                    <span><strong>All Barangays</strong></span>
                                </div>
                                <div class="target-grid" id="annTargetGrid">
                                    <?php foreach ($barangays as $b): ?>
                                    <label class="target-item" id="at_<?php echo $b['barangay_id']; ?>">
                                        <input type="checkbox" class="ann-target-check" value="<?php echo $b['barangay_id']; ?>" onchange="onAnnTargetChange()">
                                        <span><?php echo htmlspecialchars($b['barangay_name']); ?> <small style="color:#888;">(<?php echo intval($b['households']); ?> HH)</small></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-actions">
                                <label class="pin-check"><input type="checkbox" id="annPinned"> Pin</label>
                                <button class="btn-post" onclick="postAnnouncement()"><i class="fas fa-paper-plane"></i> Post</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="announce-list" id="announceList">
                    <div class="empty"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const barangays = <?php echo json_encode($barangays); ?>;

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.admin-profile'))document.getElementById("dropdownMenu").classList.remove("show");}

function toggleAnnTarget(){
    const w=document.getElementById('annTargetAllWrap'),c=document.getElementById('annTargetAll'),g=document.getElementById('annTargetGrid');
    if(c.checked){c.checked=false;w.classList.remove('active');g.style.display='grid';}
    else{c.checked=true;w.classList.add('active');g.style.display='none';
    document.querySelectorAll('.ann-target-check').forEach(x=>x.checked=false);
    document.querySelectorAll('#annTargetGrid .target-item').forEach(x=>x.classList.remove('selected'));}
}
function onAnnTargetChange(){
    document.querySelectorAll('#annTargetGrid .target-item').forEach(i=>{
        i.classList.toggle('selected',i.querySelector('input').checked);});
}

function postAnnouncement(){
    const title=document.getElementById('annTitle').value.trim();
    const msg=document.getElementById('annMsg').value.trim();
    const pinned=document.getElementById('annPinned').checked?1:0;
    const all=document.getElementById('annTargetAll').checked?1:0;
    if(!title||!msg){alert('Fill in title and message.');return;}
    const fd=new FormData();fd.append('action','post');fd.append('title',title);fd.append('message',msg);fd.append('is_pinned',pinned);fd.append('target_all',all);
    if(!all)document.querySelectorAll('.ann-target-check:checked').forEach(c=>fd.append('target_ids[]',c.value));
    fetch('announcement_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>{
        document.getElementById('annTitle').value='';document.getElementById('annMsg').value='';document.getElementById('annPinned').checked=false;
        toggleAnnTarget();loadAnnouncements();});
}

function deleteAnn(id){
    if(!confirm('Delete?'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetch('announcement_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>loadAnnouncements());
}
function togglePin(id,val){
    const fd=new FormData();fd.append('action','pin');fd.append('id',id);fd.append('pinned',val);
    fetch('announcement_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>loadAnnouncements());
}

function loadAnnouncements(){
    fetch('announcement_api.php?action=list').then(r=>r.json()).then(data=>renderAnnouncements(data));
}
function renderAnnouncements(items){
    const el=document.getElementById('announceList');
    if(!items.length){el.innerHTML='<div class="empty"><i class="fas fa-bullhorn"></i><p>No announcements</p></div>';return;}
    el.innerHTML=items.map(a=>{
        const t=new Date(a.created_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
        const pin=a.is_pinned==1?'<span class="badge-pin"><i class="fas fa-thumbtack"></i></span>':'';
        const tgt=a.target_all?'<span class="badge-all"><i class="fas fa-globe"></i> All</span>':
            '<span class="badge-target"><i class="fas fa-bullseye"></i> '+a.targets.map(x=>x.barangay_name).join(', ')+'</span>';
        const pb=a.is_pinned==1?
            '<button class="btn-unpin" onclick="togglePin('+a.announcement_id+',0)"><i class="fas fa-thumbtack"></i></button>':
            '<button class="btn-pin" onclick="togglePin('+a.announcement_id+',1)"><i class="fas fa-thumbtack"></i></button>';
        return '<div class="announce-card '+(a.is_pinned==1?'pinned':'')+'">'+
            '<div class="announce-top"><div class="announce-title">'+esc(a.title)+'</div>'+
            '<div class="announce-badges">'+pin+tgt+'</div></div>'+
            '<div class="announce-msg">'+esc(a.message)+'</div>'+
            '<div class="announce-meta"><span><i class="fas fa-user-shield"></i> '+esc(a.admin_name||'Admin')+' · '+t+'</span>'+
            '<div class="announce-actions">'+pb+
            '<button class="btn-delete" onclick="deleteAnn('+a.announcement_id+')"><i class="fas fa-trash"></i></button>'+
            '</div></div></div>';
    }).join('');
}

function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):'';}

loadAnnouncements();
setInterval(loadAnnouncements,15000);
</script>
</body>
</html>
