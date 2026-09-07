<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';
$barangay_id = intval($_SESSION['barangay_id']);
$barangay_name = $_SESSION['barangay_name'] ?? 'Barangay';

// this barangay's own logo (stored in barangay_details, relative to this folder)
$my_logo = 'yana.png';
$r = $conn->query("SELECT logo FROM barangay_details WHERE barangay_id=$barangay_id");
if ($r && $row = $r->fetch_assoc()) {
    if (!empty($row['logo'])) $my_logo = $row['logo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages - <?php echo htmlspecialchars($barangay_name); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Roboto','Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.4rem;}
.profile-area{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.profile-area:hover{background:rgba(255,255,255,0.25);}
.profile-area img{width:36px;height:36px;border-radius:50%;border:2px solid white;object-fit:cover;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

/* ---- LAYOUT: accounts side panel + group chat ---- */
.msg-layout{display:flex;height:calc(100vh - 66px);overflow:hidden;}

/* ACCOUNTS PANEL */
.accounts-panel{width:310px;min-width:310px;background:#fff;border-right:1px solid #e0e0e0;display:flex;flex-direction:column;}
.panel-header{padding:18px 20px 14px;border-bottom:1px solid #f0f0f0;background:#fafafa;}
.panel-header h3{font-size:1.05rem;color:#202124;display:flex;align-items:center;gap:8px;}
.panel-header h3 i{color:#0072C6;}
.panel-header p{font-size:.78rem;color:#5f6368;margin-top:2px;}
.acc-search{margin-top:10px;position:relative;}
.acc-search input{width:100%;padding:9px 14px 9px 34px;border:2px solid #e0e0e0;border-radius:10px;font-size:.85rem;outline:none;}
.acc-search input:focus{border-color:#0072C6;}
.acc-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9aa0a6;font-size:.8rem;}
.acc-items{flex:1;overflow-y:auto;}
.acc-item{display:flex;align-items:center;gap:12px;padding:12px 18px;cursor:default;border-bottom:1px solid #f5f5f5;transition:background .15s;}
.acc-item:hover{background:#f0f7ff;}
.acc-avatar{position:relative;width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0072C6,#005999);color:#fff;font-weight:600;font-size:.9rem;}
.acc-avatar img{width:100%;height:100%;object-fit:cover;}
.acc-dot{position:absolute;bottom:1px;right:1px;width:12px;height:12px;border-radius:50%;border:2px solid #fff;background:#9aa0a6;}
.acc-dot.on{background:#188038;}
.acc-info{flex:1;min-width:0;}
.acc-name{font-weight:600;font-size:.9rem;color:#202124;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.acc-name .you-tag{font-size:.62rem;font-weight:700;color:#0072C6;background:#e3f2fd;border-radius:10px;padding:1px 7px;margin-left:6px;}
.acc-sub{font-size:.75rem;color:#5f6368;margin-top:2px;}
.acc-empty{padding:30px 20px;text-align:center;color:#9aa0a6;font-size:.85rem;}
.sec-label{display:flex;align-items:center;gap:8px;padding:10px 18px 6px;font-size:.72rem;font-weight:700;color:#5f6368;text-transform:uppercase;letter-spacing:.5px;background:#fafafa;border-bottom:1px solid #f0f0f0;}
.sec-label i{color:#0072C6;}

/* GROUP CHAT */
.group-chat{flex:1;display:flex;flex-direction:column;background:#f8f9fa;min-width:0;}
.gc-head{display:flex;align-items:center;gap:14px;padding:14px 24px;background:#fff;border-bottom:1px solid #e0e0e0;}
.gc-avatars{display:flex;flex-shrink:0;}
.gc-avatars .ga{width:40px;height:40px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0072C6,#005999);color:#fff;font-weight:600;font-size:.8rem;border:2px solid #fff;}
.gc-avatars .ga img{width:100%;height:100%;object-fit:cover;}
.gc-avatars .ga+.ga{margin-left:-12px;}
.gc-avatars .ga.more{background:#5f6368;}
.gc-head-info h3{font-size:1.05rem;color:#202124;display:flex;align-items:center;gap:8px;}
.gc-head-info h3 i{color:#0072C6;}
.gc-head-info p{font-size:.78rem;color:#5f6368;}
.gc-messages{flex:1;overflow-y:auto;padding:22px 26px;display:flex;flex-direction:column;}
.gc-msg{display:flex;gap:10px;max-width:70%;margin-bottom:6px;}
.gc-avatar{width:36px;height:36px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0072C6,#005999);color:#fff;font-weight:600;font-size:.75rem;margin-top:2px;}
.gc-avatar img{width:100%;height:100%;object-fit:cover;}
.gc-bubble{background:#fff;border-radius:14px;padding:9px 14px;box-shadow:0 1px 2px rgba(0,0,0,.08);}
.gc-name{font-size:.72rem;font-weight:600;margin-bottom:3px;}
.gc-text{font-size:.9rem;line-height:1.45;color:#202124;word-wrap:break-word;}
.gc-time{font-size:.65rem;color:#9aa0a6;margin-top:4px;text-align:right;}
.gc-msg.own{align-self:flex-end;flex-direction:row-reverse;}
.gc-msg.own .gc-bubble{background:#d2e3fc;}
.gc-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#9aa0a6;}
.gc-empty i{font-size:3.2rem;margin-bottom:12px;}
.gc-empty p{font-size:1rem;}
.gc-input{padding:14px 20px;background:#fff;border-top:1px solid #e0e0e0;display:flex;gap:10px;align-items:center;}
.gc-input input{flex:1;padding:11px 18px;border:1px solid #dadce0;border-radius:24px;font-size:.9rem;outline:none;}
.gc-input input:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.2);}
.gc-input button{width:44px;height:44px;border-radius:50%;border:none;background:#0072C6;color:#fff;font-size:1.05rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;}
.gc-input button:hover{background:#005999;}
.gc-input button:disabled{background:#dadce0;cursor:default;}
@media (max-width:900px){.accounts-panel{width:240px;min-width:240px;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Group Chat</h1>
            <div style="position:relative;">
                <div class="profile-area" onclick="toggleDropdown()">
                    <img src="<?php echo htmlspecialchars($my_logo); ?>" alt="Barangay Logo" onerror="this.src='yana.png'">
                    <span><?php echo htmlspecialchars($barangay_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="msg-layout">
            <!-- ACCOUNTS PANEL -->
            <aside class="accounts-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-users"></i> Accounts</h3>
                    <p>DSWD & all barangays in the group chat</p>
                    <div class="acc-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="accSearch" placeholder="Search account..." oninput="renderAccounts()">
                    </div>
                </div>
                <div class="acc-items" id="accItems"></div>
            </aside>

            <!-- GROUP CHAT -->
            <section class="group-chat">
                <div class="gc-head">
                    <div class="gc-avatars" id="gcAvatars"></div>
                    <div class="gc-head-info">
                        <h3><i class="fas fa-users"></i> DSWD - Barangay Group Chat</h3>
                        <p id="gcMembers">Members</p>
                    </div>
                </div>
                <div class="gc-messages" id="gcMessages"></div>
                <div class="gc-input">
                    <input type="text" id="msgInput" placeholder="Type a message to the group..." onkeydown="if(event.key==='Enter')sendGroup()">
                    <button onclick="sendGroup()" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
const PALETTE = ['#1a73e8','#188038','#e37400','#a142f4','#d93025','#12a4af','#7b1fa2','#00695c','#c5221f','#5f6368'];
const MY_NAME = <?php echo json_encode($barangay_name); ?>;
const MY_LOGO = <?php echo json_encode($my_logo); ?>;
let accounts = [];
let lastMsgCount = 0;

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.profile-area'))document.getElementById("dropdownMenu").classList.remove("show");}

function heartbeat(){ fetch('message_api.php?action=heartbeat').then(r=>r.json()).catch(()=>{}); }

function initials(name){
    name=(name||'?').trim();
    const parts=name.split(/\s+/);
    if(parts.length>=2) return (parts[0][0]+parts[1][0]).toUpperCase();
    return name.substring(0,2).toUpperCase();
}
function avatarFallback(el){
    const name=el.getAttribute('data-name')||'?';
    const fg=el.getAttribute('data-fg')||'#0072C6';
    el.outerHTML='<span style="color:'+fg+'">'+initials(name)+'</span>';
}
function avatarHtml(url,name,fg){
    if(url) return '<img src="'+escHtml(url)+'" data-name="'+escHtml(name)+'" data-fg="'+fg+'" onerror="avatarFallback(this)" alt="">';
    return '<span style="color:'+fg+'">'+initials(name)+'</span>';
}
function nameColor(id){
    const n=(id||0);
    let sum=0; const s=String(n);
    for(let i=0;i<s.length;i++) sum+=s.charCodeAt(i);
    return PALETTE[sum%PALETTE.length];
}
function escHtml(s){ return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
function fmtTime(t){
    const d=new Date(t);
    return d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
}

/* ---- ACCOUNTS PANEL ---- */
function loadAccounts(){
    fetch('message_api.php?action=group_accounts')
        .then(r=>r.json())
        .then(data=>{ accounts=data; renderAccounts(); renderHeaderAvatars(); })
        .catch(()=>{});
}
function renderAccounts(){
    const q=(document.getElementById('accSearch').value||'').toLowerCase();
    const list=document.getElementById('accItems');
    const filtered=accounts.filter(a=>(a.name||'').toLowerCase().includes(q));
    const you=filtered.filter(a=>a.is_me);
    const others=filtered.filter(a=>!a.is_me);

    let html='';
    if(you.length){
        html+='<div class="sec-label"><i class="fas fa-user-circle"></i> You</div>';
        html+=you.map(accItemHtml).join('');
    }
    if(others.length){
        html+='<div class="sec-label"><i class="fas fa-users"></i> Accounts ('+others.length+')</div>';
        html+=others.map(accItemHtml).join('');
    }
    if(!html) html='<div class="acc-empty">No accounts found</div>';
    list.innerHTML=html;
}
function accItemHtml(a){
    const you=a.is_me?'<span class="you-tag">You</span>':'';
    const dot='<div class="acc-dot '+(a.is_online?'on':'')+'"></div>';
    return '<div class="acc-item">'+
        '<div class="acc-avatar">'+avatarHtml(a.logo,a.name,'#fff')+dot+'</div>'+
        '<div class="acc-info"><div class="acc-name">'+escHtml(a.name)+you+'</div>'+
        '<div class="acc-sub">'+escHtml(a.sub||'')+'</div></div></div>';
}
function renderHeaderAvatars(){
    const wrap=document.getElementById('gcAvatars');
    const shown=accounts.slice(0,4);
    const html=shown.map(a=>'<div class="ga">'+avatarHtml(a.logo,a.name,'#fff')+'</div>').join('');
    const extra=accounts.length>4?'<div class="ga more">+'+((accounts.length-4).toString())+'</div>':'';
    wrap.innerHTML=html+extra;
    document.getElementById('gcMembers').textContent=accounts.length+' member'+(accounts.length===1?'':'s');
}

/* ---- GROUP CHAT ---- */
function loadGroup(){
    fetch('message_api.php?action=group_fetch')
        .then(r=>r.json())
        .then(data=>{
            renderGroup(data.messages||[]);
            if(typeof data.member_count!=='undefined') document.getElementById('gcMembers').textContent=data.member_count+' member'+(data.member_count===1?'':'s');
        })
        .catch(()=>{});
}
function renderGroup(msgs){
    const container=document.getElementById('gcMessages');
    const wasAtBottom=container.scrollHeight-container.scrollTop-container.clientHeight<80;

    if(!msgs.length){
        container.innerHTML='<div class="gc-empty"><i class="fas fa-users"></i><p>No messages yet. Start the group conversation!</p></div>';
    } else {
        container.innerHTML=msgs.map(m=>{
            const own=m.own?' own':'';
            const fg=nameColor(m.sender_id);
            const time=fmtTime(m.created_at);
            return '<div class="gc-msg'+own+'">'+
                '<div class="gc-avatar">'+avatarHtml(m.sender_logo,m.sender_name,'#fff')+'</div>'+
                '<div><div class="gc-bubble">'+
                    (own?'':'<div class="gc-name" style="color:'+fg+'">'+escHtml(m.sender_name)+'</div>')+
                    '<div class="gc-text">'+escHtml(m.message)+'</div>'+
                    '<div class="gc-time">'+time+'</div>'+
                '</div></div></div>';
        }).join('');
    }
    const count=msgs.length;
    if(count>lastMsgCount){ container.scrollTop=container.scrollHeight; }
    lastMsgCount=count;
    if(wasAtBottom) container.scrollTop=container.scrollHeight;
}
function sendGroup(){
    const input=document.getElementById('msgInput');
    const msg=input.value.trim();
    if(!msg) return;
    input.value='';
    document.getElementById('sendBtn').disabled=true;

    const fd=new FormData();
    fd.append('action','group_send');
    fd.append('message',msg);

    fetch('message_api.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(()=>{ document.getElementById('sendBtn').disabled=false; loadGroup(); loadAccounts(); })
        .catch(()=>{ document.getElementById('sendBtn').disabled=false; input.value=msg; });
}

heartbeat();
setInterval(heartbeat,30000);
loadAccounts();
loadGroup();
setInterval(loadAccounts,15000);
setInterval(loadGroup,3000);
</script>
</body>
</html>
