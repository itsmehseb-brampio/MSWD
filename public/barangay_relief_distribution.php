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
<title>Relief Goods Distribution - <?php echo htmlspecialchars($barangay_name); ?></title>
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

.page-body{padding:24px 30px;}
.page-title{font-size:1.15rem;font-weight:700;color:#333;margin-bottom:4px;}
.page-sub{font-size:0.83rem;color:#888;margin-bottom:20px;}

.schedule-list{display:flex;flex-direction:column;gap:16px;}
.schedule-card{border:1px solid #e8e8e8;border-radius:12px;background:white;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;}
.schedule-head{padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:flex-start;gap:10px;}
.schedule-title{font-weight:700;font-size:1rem;color:#333;}
.schedule-status{padding:4px 12px;border-radius:12px;font-size:0.7rem;font-weight:600;flex-shrink:0;}
.status-upcoming{background:#fff3cd;color:#856404;}
.status-ongoing{background:#d4edda;color:#155724;}
.status-completed{background:#d1ecf1;color:#0c5460;}
.schedule-body{padding:14px 20px;}
.schedule-desc{font-size:0.85rem;color:#666;margin-bottom:10px;}
.schedule-meta{display:flex;flex-wrap:wrap;gap:14px;font-size:0.8rem;color:#777;margin-bottom:10px;}
.schedule-meta i{margin-right:4px;color:#999;}
.item-badge{background:#fff3cd;color:#856404;padding:4px 12px;border-radius:12px;font-size:0.78rem;font-weight:600;display:inline-block;margin:2px;}
.hh-note{margin:8px 0;padding:6px 12px;background:linear-gradient(135deg,#fff8e1,#fff3cd);border:1px solid #ffe082;border-radius:8px;font-size:0.82rem;color:#795548;font-weight:600;display:flex;align-items:center;gap:6px;}

.report-box{border-top:2px solid #f0f0f0;padding:14px 20px;}
.report-box.confirmed{background:#f7fdf8;border-top:2px solid #28a745;}
.report-box.pending{background:#fffdf5;border-top:2px solid #fd7e14;}
.report-label{font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.report-label.conf{color:#28a745;}
.report-label.pen{color:#fd7e14;}
.btn-confirm{padding:9px 18px;background:#28a745;color:white;border:none;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;}
.btn-confirm:hover{background:#218838;}
.report-detail{font-size:0.85rem;color:#444;line-height:1.6;}
.report-detail strong{color:#333;}
.report-narrative{margin-top:8px;padding:10px 14px;background:white;border:1px solid #d4edda;border-radius:8px;font-size:0.85rem;line-height:1.6;color:#444;white-space:pre-wrap;}
.report-docs{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
.report-docs a{position:relative;display:block;}
.report-docs img{width:120px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform .2s;}
.report-docs img:hover{transform:scale(1.06);}
.report-docs .doc-pdf{display:flex;align-items:center;justify-content:center;width:120px;height:100px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:0.75rem;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
.report-empty{font-size:0.85rem;color:#888;}

.empty{text-align:center;padding:40px;color:#bbb;}
.empty i{font-size:2.5rem;margin-bottom:8px;display:block;}

/* MODAL */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:2000;align-items:flex-start;justify-content:center;padding:40px 20px;overflow-y:auto;}
.modal-overlay.show{display:flex;}
.modal{background:white;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,0.25);width:100%;max-width:620px;overflow:hidden;}
.modal-head{padding:16px 22px;background:linear-gradient(90deg,#28a745,#1e7e34);color:white;display:flex;justify-content:space-between;align-items:center;}
.modal-head h3{font-size:1rem;}
.modal-close{background:rgba(255,255,255,0.2);border:none;color:white;width:30px;height:30px;border-radius:50%;cursor:pointer;}
.modal-body{padding:20px 22px;display:flex;flex-direction:column;gap:12px;}
.form-group label{display:block;font-size:0.8rem;font-weight:600;color:#555;margin-bottom:5px;}
.form-group input,.form-group textarea{width:100%;padding:9px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;font-family:inherit;}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:#28a745;}
.form-group textarea{height:120px;resize:vertical;}
.form-group input[type="file"]{padding:8px;border-style:dashed;background:#fafafa;}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:4px;}
.btn-cancel{padding:9px 16px;background:#f8f9fa;border:none;border-radius:8px;color:#666;font-weight:600;cursor:pointer;}
.btn-save{padding:9px 18px;background:#28a745;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;}
.btn-save:hover{background:#218838;}
.hint{font-size:0.75rem;color:#999;}

@media(max-width:768px){.page-body{padding:16px;}.schedule-head{flex-direction:column;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Relief Goods Distribution</h1>
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

        <div class="page-body">
            <div class="page-title"><i class="fas fa-box-open" style="color:#fd7e14;"></i> Relief Goods Distribution</div>
            <div class="page-sub">Confirm receipt of relief goods and submit your narrative report with documentation.</div>
            <div class="schedule-list" id="scheduleList">
                <div class="empty"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Receipt Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-clipboard-check"></i> Confirm Receipt of Relief Goods</h3>
            <button class="modal-close" onclick="closeConfirm()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Confirmed By (Name / Position)</label>
                <input type="text" id="confName" placeholder="e.g. Brgy. Captain Juan Dela Cruz">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-check"></i> Date &amp; Time Received</label>
                <input type="datetime-local" id="confDate">
                <div class="hint">Date and timestamp when the relief goods were received.</div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Narrative Report</label>
                <textarea id="confNarrative" placeholder="Describe the distribution: number of beneficiaries, how the goods were distributed, remarks, etc."></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-camera"></i> Documentation (Photos / PDF)</label>
                <input type="file" id="confDocs" accept="image/*,application/pdf" multiple>
                <div class="hint">Upload photos of the distribution as documentation.</div>
            </div>
            <div class="form-actions">
                <button class="btn-cancel" onclick="closeConfirm()">Cancel</button>
                <button class="btn-save" id="btnSubmit" onclick="submitConfirm()"><i class="fas fa-check"></i> Confirm Receipt</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSchedule = null;

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.profile-area'))document.getElementById("dropdownMenu").classList.remove("show");}

function loadSchedules(){
    fetch('relief_distribution_api.php?action=list').then(r=>r.json()).then(data=>renderSchedules(data)).catch(()=>{});
}

function nowLocal(){
    const d=new Date();const p=n=>String(n).padStart(2,'0');
    return d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'T'+p(d.getHours())+':'+p(d.getMinutes());
}

function openConfirm(id){
    currentSchedule={schedule_id:id};
    document.getElementById('confName').value='';
    document.getElementById('confDate').value=nowLocal();
    document.getElementById('confNarrative').value='';
    document.getElementById('confDocs').value='';
    document.getElementById('confirmModal').classList.add('show');
}
function closeConfirm(){document.getElementById('confirmModal').classList.remove('show');}

function submitConfirm(){
    const name=document.getElementById('confName').value.trim();
    const dt=document.getElementById('confDate').value;
    const narrative=document.getElementById('confNarrative').value.trim();
    if(!name){alert('Enter the name of the person confirming receipt.');return;}
    if(!narrative){alert('Narrative report is required.');return;}
    const btn=document.getElementById('btnSubmit');
    btn.disabled=true;
    const fd=new FormData();
    fd.append('action','confirm');
    fd.append('schedule_id',currentSchedule.schedule_id);
    fd.append('confirmed_by',name);
    fd.append('received_at',dt);
    fd.append('narrative',narrative);
    const files=document.getElementById('confDocs').files;
    for(let i=0;i<files.length;i++)fd.append('documents[]',files[i]);
    fetch('relief_distribution_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
        btn.disabled=false;
        if(res.error){alert(res.error);return;}
        closeConfirm();
        loadSchedules();
    }).catch(()=>{btn.disabled=false;alert('Submission failed.');});
}

function renderSchedules(items){
    const el=document.getElementById('scheduleList');
    if(!items.length){el.innerHTML='<div class="empty"><i class="fas fa-box-open"></i><p>No relief schedules yet</p></div>';return;}
    el.innerHTML=items.map(s=>{
        const d=new Date(s.distribution_date).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});
        const tm=s.distribution_time?s.distribution_time.substring(0,5):'';
        const itemsHtml=s.items.map(i=>'<span class="item-badge"><i class="fas fa-gift"></i> '+esc(i.item_name)+' — '+i.quantity+' '+esc(i.unit)+'</span>').join('');
        let reportHtml='';
        if(s.report){
            const rep=s.report;
            const rd=new Date(rep.received_at.replace(' ','T'));
            const rdt=rd.toLocaleString('en-US',{month:'long',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
            const docs=(rep.documents||[]).map(doc=>{
                const ext=doc.file_path.split('.').pop().toLowerCase();
                if(ext==='pdf')return '<a class="doc-pdf" href="'+doc.file_path+'" target="_blank"><i class="fas fa-file-pdf" style="font-size:1.6rem;margin-right:5px;"></i>PDF</a>';
                return '<a href="'+doc.file_path+'" target="_blank"><img src="'+doc.file_path+'" alt="Documentation"></a>';
            }).join('');
            reportHtml='<div class="report-box confirmed">'+
                '<div class="report-label conf"><i class="fas fa-check-circle"></i> Receipt Confirmed</div>'+
                '<div class="report-detail"><strong>Received:</strong> '+rdt+'<br>'+
                '<strong>Confirmed by:</strong> '+esc(rep.confirmed_by)+'<br>'+
                '<strong>Submitted:</strong> '+new Date(rep.created_at.replace(' ','T')).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'})+'</div>'+
                '<div class="report-narrative">'+esc(rep.narrative)+'</div>'+
                (docs?'<div class="report-docs">'+docs+'</div>':'')+'</div>';
        } else {
            reportHtml='<div class="report-box pending">'+
                '<div class="report-label pen"><i class="fas fa-hourglass-half"></i> Awaiting Receipt Confirmation</div>'+
                '<button class="btn-confirm" onclick="openConfirm('+s.schedule_id+')"><i class="fas fa-clipboard-check"></i> Confirm Receipt &amp; Submit Report</button></div>';
        }
        return '<div class="schedule-card">'+
            '<div class="schedule-head"><div><div class="schedule-title">'+esc(s.title)+'</div>'+
            (s.location?'<div class="schedule-meta" style="margin-bottom:0;margin-top:6px;"><span><i class="fas fa-map-marker-alt"></i> '+esc(s.location)+'</span></div>':'')+'</div>'+
            '<span class="schedule-status status-'+s.status+'">'+s.status.toUpperCase()+'</span></div>'+
            '<div class="schedule-body">'+
            (s.description?'<div class="schedule-desc">'+esc(s.description)+'</div>':'')+
            '<div class="schedule-meta"><span><i class="fas fa-calendar-day"></i> '+d+'</span>'+
            (tm?'<span><i class="fas fa-clock"></i> '+tm+'</span>':'')+'</div>'+
            (itemsHtml?'<div class="schedule-items">'+itemsHtml+'</div>':'')+
            '<div class="hh-note"><i class="fas fa-home"></i> '+esc(s.barangay_name)+' — <strong>'+s.barangay_households+' households</strong></div>'+
            '</div>'+reportHtml+'</div>';
    }).join('');
}

function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):'';}

loadSchedules();
setInterval(loadSchedules,15000);
</script>
</body>
</html>
