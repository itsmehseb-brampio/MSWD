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
<title>Relief Goods Distribution - Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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

.page-body{padding:24px 30px;}
.page-title{font-size:1.15rem;font-weight:700;color:#333;margin-bottom:4px;}
.page-sub{font-size:0.83rem;color:#888;margin-bottom:20px;}

.summary-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:22px;}
.summary-card{background:white;border-radius:12px;padding:16px 18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #0072C6;}
.summary-card.green{border-left-color:#28a745;}
.summary-card.orange{border-left-color:#fd7e14;}
.summary-num{font-size:1.5rem;font-weight:700;color:#333;}
.summary-lbl{font-size:0.75rem;color:#888;margin-top:2px;}

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
.item-badge{background:#f0f0f0;color:#555;padding:3px 10px;border-radius:10px;font-size:0.75rem;display:inline-block;margin:2px;}
.target-note{font-size:0.78rem;color:#888;margin:8px 0;}

.reports-section{padding:0 20px 14px;}
.reports-title{font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#555;margin:12px 0 10px;display:flex;align-items:center;gap:8px;}
.report-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;}
.report-card{border:1px solid #d4edda;background:#f7fdf8;border-radius:10px;padding:12px 14px;}
.report-card .brgy{font-weight:700;font-size:0.88rem;color:#1e7e34;}
.report-card .rcv{font-size:0.8rem;color:#555;margin-top:4px;}
.report-card .by{font-size:0.78rem;color:#888;margin-top:2px;}
.report-card .nar{margin-top:8px;padding:8px 10px;background:white;border:1px solid #d4edda;border-radius:8px;font-size:0.8rem;color:#444;line-height:1.5;white-space:pre-wrap;}
.report-docs{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;}
.report-docs img{width:96px;height:80px;object-fit:cover;border-radius:6px;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
.report-docs .doc-pdf{display:flex;align-items:center;justify-content:center;width:96px;height:80px;border-radius:6px;background:#f8d7da;color:#721c24;font-size:0.7rem;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
.report-actions{margin-top:8px;text-align:right;}
.report-actions button{padding:4px 10px;border-radius:6px;border:none;background:#f8d7da;color:#721c24;font-size:0.72rem;font-weight:600;cursor:pointer;}
.report-actions button:hover{background:#f5c6cb;}
.no-reports{font-size:0.83rem;color:#999;padding:10px 0;}

.empty{text-align:center;padding:40px;color:#bbb;}
.empty i{font-size:2.5rem;margin-bottom:8px;display:block;}

/* CREATE SCHEDULE PANEL */
.panel{background:white;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:20px;}
.panel-header{padding:14px 20px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:8px;font-weight:700;font-size:0.95rem;color:#333;cursor:pointer;user-select:none;}
.panel-header .chev{margin-left:auto;transition:transform .2s;color:#5f6368;font-size:.8rem;}
.panel-header.open .chev{transform:rotate(180deg);}
.panel-body{padding:16px 20px;}
.schedule-form{display:flex;flex-direction:column;gap:12px;}
.form-group label{display:block;font-size:0.8rem;font-weight:600;color:#555;margin-bottom:5px;}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:9px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;font-family:inherit;}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:#0072C6;}
.form-group textarea{height:60px;resize:vertical;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.target-section{margin-top:2px;}
.target-all{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0f8ff;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:0.82rem;}
.target-all.active{border-color:#0072C6;background:#e3f2fd;}
.target-all input{accent-color:#0072C6;width:15px;height:15px;}
.target-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;max-height:120px;overflow-y:auto;padding:4px 0;display:none;}
.target-item{display:flex;align-items:center;gap:6px;padding:6px 10px;background:#f9f9f9;border:1px solid #e8e8e8;border-radius:6px;cursor:pointer;font-size:0.8rem;}
.target-item.selected{background:#e3f2fd;border-color:#0072C6;}
.target-item input{accent-color:#0072C6;width:14px;height:14px;}
.household-summary{margin-top:8px;padding:8px 12px;background:linear-gradient(135deg,#fff8e1,#fff3cd);border:1px solid #ffe082;border-radius:8px;font-size:0.8rem;color:#795548;font-weight:600;display:flex;align-items:center;gap:6px;}
.items-section{margin-top:2px;}
.items-list{display:flex;flex-direction:column;gap:6px;margin-bottom:8px;}
.item-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:6px;align-items:center;}
.item-row input{padding:7px 10px;border:2px solid #e0e0e0;border-radius:6px;font-size:0.82rem;}
.item-row input:focus{outline:none;border-color:#0072C6;}
.btn-remove-item{width:28px;height:28px;border-radius:50%;border:none;background:#f8d7da;color:#721c24;cursor:pointer;font-size:0.8rem;}
.btn-add-item{padding:6px 12px;border:2px dashed #ccc;border-radius:6px;background:none;color:#888;cursor:pointer;font-size:0.8rem;font-weight:600;}
.btn-add-item:hover{border-color:#0072C6;color:#0072C6;}
.form-actions{display:flex;gap:10px;align-items:center;margin-top:4px;}
.btn-post{padding:9px 20px;background:#0072C6;color:white;border:none;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;}
.btn-post:hover{background:#005999;}
.btn-post.green{background:#28a745;}
.btn-post.green:hover{background:#218838;}
.schedule-actions{display:flex;gap:6px;margin-top:10px;}
.schedule-actions select{padding:5px 10px;border-radius:6px;border:none;font-size:0.75rem;font-weight:600;cursor:pointer;background:#f8f9fa;border:1px solid #ddd;color:#555;}
.schedule-actions button{padding:5px 10px;border-radius:6px;border:none;font-size:0.75rem;font-weight:600;cursor:pointer;}
.btn-delete{background:#f8d7da;color:#721c24;}
.btn-delete:hover{background:#f5c6cb;}
.chart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:24px;}
.chart-card .panel-header{cursor:default;}
.chart-wrap{position:relative;height:270px;padding:14px 18px 10px;}
.chart-empty{display:none;text-align:center;color:#bbb;padding:60px 10px;font-size:0.85rem;}
@media(max-width:768px){.page-body{padding:16px;}.schedule-head{flex-direction:column;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Relief Goods Distribution</h1>
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

        <div class="page-body">
            <div class="page-title"><i class="fas fa-box-open" style="color:#fd7e14;"></i> Relief Goods Distribution Reports</div>
            <div class="page-sub">Track receipt confirmations, narrative reports, and documentation from barangays.</div>

            <div class="summary-row">
                <div class="summary-card"><div class="summary-num" id="sumSchedules">0</div><div class="summary-lbl">Total Schedules</div></div>
                <div class="summary-card green"><div class="summary-num" id="sumConfirmed">0</div><div class="summary-lbl">Receipts Confirmed</div></div>
                <div class="summary-card orange"><div class="summary-num" id="sumPending">0</div><div class="summary-lbl">Awaiting Confirmation</div></div>
            </div>

            <!-- VISUAL REPORTS -->
            <div class="chart-row">
                <div class="panel chart-card">
                    <div class="panel-header"><i class="fas fa-chart-pie" style="color:#fd7e14;"></i> Relief Goods by Item</div>
                    <div class="chart-wrap"><canvas id="itemChart"></canvas></div>
                    <div class="chart-empty" id="itemChartEmpty">No relief items yet</div>
                </div>
                <div class="panel chart-card">
                    <div class="panel-header"><i class="fas fa-chart-pie" style="color:#28a745;"></i> Confirmation Status</div>
                    <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
                    <div class="chart-empty" id="statusChartEmpty">No schedules yet</div>
                </div>
                <div class="panel chart-card">
                    <div class="panel-header"><i class="fas fa-chart-bar" style="color:#0072C6;"></i> Confirmations by Barangay</div>
                    <div class="chart-wrap"><canvas id="brgyChart"></canvas></div>
                    <div class="chart-empty" id="brgyChartEmpty">No reports yet</div>
                </div>
            </div>

            <!-- CREATE RELIEF SCHEDULE -->
            <div class="panel">
                <div class="panel-header open" onclick="toggleCreatePanel(this)">
                    <i class="fas fa-plus-circle" style="color:#28a745;"></i> Create Relief Schedule
                    <i class="fas fa-chevron-down chev"></i>
                </div>
                <div class="panel-body" id="createPanelBody">
                    <div class="schedule-form">
                        <div class="form-group">
                            <input type="text" id="schTitle" placeholder="Schedule title...">
                        </div>
                        <div class="form-group">
                            <textarea id="schDesc" placeholder="Description..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" id="schDate">
                            </div>
                            <div class="form-group">
                                <label>Time</label>
                                <input type="time" id="schTime">
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" id="schLocation" placeholder="Location...">
                        </div>
                        <div class="target-section">
                            <div class="target-all active" id="schTargetAllWrap" onclick="toggleSchTarget()">
                                <input type="checkbox" id="schTargetAll" checked>
                                <span><strong>All Barangays</strong></span>
                            </div>
                            <div class="target-grid" id="schTargetGrid">
                                <?php foreach ($barangays as $b): ?>
                                <label class="target-item" id="st_<?php echo $b['barangay_id']; ?>">
                                    <input type="checkbox" class="sch-target-check" value="<?php echo $b['barangay_id']; ?>" onchange="onSchTargetChange()">
                                    <span><?php echo htmlspecialchars($b['barangay_name']); ?> <small style="color:#888;">(<?php echo intval($b['households']); ?> HH)</small></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="household-summary">
                                <i class="fas fa-home"></i> <span id="schHHText">All — <?php echo array_sum(array_column($barangays, 'households')); ?> households total</span>
                            </div>
                        </div>
                        <div class="items-section">
                            <label style="font-size:0.8rem;font-weight:600;color:#555;"><i class="fas fa-list"></i> Relief Goods Items</label>
                            <div class="items-list" id="itemsList"></div>
                            <button class="btn-add-item" onclick="addItem()"><i class="fas fa-plus"></i> Add Item</button>
                        </div>
                        <div class="form-actions">
                            <button class="btn-post green" onclick="addSchedule()"><i class="fas fa-plus"></i> Create Schedule</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="schedule-list" id="scheduleList">
                <div class="empty"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
const barangays = <?php echo json_encode($barangays); ?>;

function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.admin-profile'))document.getElementById("dropdownMenu").classList.remove("show");}

function toggleCreatePanel(btn){
    btn.classList.toggle('open');
    document.getElementById('createPanelBody').style.display = btn.classList.contains('open') ? '' : 'none';
}

function toggleSchTarget(){
    const w=document.getElementById('schTargetAllWrap'),c=document.getElementById('schTargetAll'),g=document.getElementById('schTargetGrid');
    if(c.checked){c.checked=false;w.classList.remove('active');g.style.display='grid';}
    else{c.checked=true;w.classList.add('active');g.style.display='none';
    document.querySelectorAll('.sch-target-check').forEach(x=>x.checked=false);
    document.querySelectorAll('#schTargetGrid .target-item').forEach(x=>x.classList.remove('selected'));}
    updateSchHH();
}
function onSchTargetChange(){
    document.querySelectorAll('#schTargetGrid .target-item').forEach(i=>{
        i.classList.toggle('selected',i.querySelector('input').checked);});
    updateSchHH();
}
function updateSchHH(){
    const all=document.getElementById('schTargetAll').checked;
    const el=document.getElementById('schHHText');
    if(all){
        const total=barangays.reduce((s,b)=>s+parseInt(b.households||0),0);
        el.textContent='All — '+total+' households total';
        return;
    }
    let total=0,count=0;
    document.querySelectorAll('.sch-target-check:checked').forEach(c=>{
        const b=barangays.find(x=>x.barangay_id==c.value);
        if(b){total+=parseInt(b.households||0);count++;}
    });
    if(count===0){el.textContent='No barangay selected';}
    else{el.textContent=count+' barangay'+(count>1?'s':'')+' — '+total+' households total';}
}

let itemCounter=0;
const defaultItems=[
    {name:'Rice',qty:10,unit:'kg'},
    {name:'Sardines',qty:6,unit:'cans'},
    {name:'Milk',qty:1,unit:'sachet'},
    {name:'Coffee',qty:1,unit:'sachet'},
    {name:'Cream Top',qty:1,unit:'sachet'},
    {name:'Pancit',qty:1,unit:'pack'},
    {name:'Corned Beef',qty:4,unit:'cans'},
    {name:'Meat Loaf',qty:2,unit:'cans'}
];
function addItem(name,qty,unit){
    const id=itemCounter++;
    const n=name||'';
    const q=qty||0;
    const u=unit||'packs';
    const html='<div class="item-row" id="item_'+id+'">'+
        '<input type="text" placeholder="Item name" class="item-name" value="'+esc(n)+'">'+
        '<input type="number" placeholder="Qty" class="item-qty" value="'+q+'" min="0">'+
        '<input type="text" placeholder="Unit" class="item-unit" value="'+esc(u)+'">'+
        '<button class="btn-remove-item" onclick="removeItem('+id+')"><i class="fas fa-times"></i></button></div>';
    document.getElementById('itemsList').insertAdjacentHTML('beforeend',html);
}
function loadDefaultItems(){
    document.getElementById('itemsList').innerHTML='';
    itemCounter=0;
    defaultItems.forEach(i=>addItem(i.name,i.qty,i.unit));
}
function removeItem(id){const e=document.getElementById('item_'+id);if(e)e.remove();}

function addSchedule(){
    const title=document.getElementById('schTitle').value.trim();
    const desc=document.getElementById('schDesc').value.trim();
    const date=document.getElementById('schDate').value;
    const time=document.getElementById('schTime').value||null;
    const loc=document.getElementById('schLocation').value.trim();
    const all=document.getElementById('schTargetAll').checked?1:0;
    if(!title||!date){alert('Fill in title and date.');return;}
    const fd=new FormData();fd.append('action','add');fd.append('title',title);fd.append('description',desc);
    fd.append('distribution_date',date);fd.append('distribution_time',time);fd.append('location',loc);fd.append('target_all',all);
    if(!all)document.querySelectorAll('.sch-target-check:checked').forEach(c=>fd.append('target_ids[]',c.value));
    document.querySelectorAll('.item-row').forEach(row=>{
        fd.append('item_names[]',row.querySelector('.item-name').value);
        fd.append('item_qty[]',row.querySelector('.item-qty').value);
        fd.append('item_unit[]',row.querySelector('.item-unit').value);
    });
    fetch('relief_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
        if(res.error){alert(res.error);return;}
        document.getElementById('schTitle').value='';document.getElementById('schDesc').value='';
        document.getElementById('schDate').value='';document.getElementById('schTime').value='';
        document.getElementById('schLocation').value='';
        loadDefaultItems();toggleSchTarget();loadSchedules();});
}
function updateStatus(id,status){
    const fd=new FormData();fd.append('action','update_status');fd.append('id',id);fd.append('status',status);
    fetch('relief_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>loadSchedules());
}
function deleteSchedule(id){
    if(!confirm('Delete this schedule?'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetch('relief_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>loadSchedules());
}

function loadSchedules(){
    fetch('relief_distribution_api.php?action=list').then(r=>r.json()).then(data=>{renderSchedules(data);renderCharts(data);}).catch(()=>{});
}

let itemChart=null,statusChart=null,brgyChart=null;
const CHART_COLORS=['#0072C6','#28a745','#fd7e14','#dc3545','#6f42c1','#20c997','#ffc107','#e83e8c','#17a2b8','#6c757d','#e8710a','#0097a7','#00c853','#5f6368'];
function chartColors(n){return Array.from({length:n},(_,i)=>CHART_COLORS[i%CHART_COLORS.length]);}
function setChartState(canvasId,emptyId,hasData){
    document.getElementById(canvasId).style.display=hasData?'':'none';
    document.getElementById(emptyId).style.display=hasData?'none':'block';
}
function pctLabel(c,unit){
    const t=c.dataset.data.reduce((a,b)=>a+b,0);
    const p=t?((c.parsed/t)*100).toFixed(1):0;
    return ' '+c.label+': '+c.parsed+' '+unit+' ('+p+'%)';
}
function renderCharts(items){
    items=items||[];
    const itemMap={};
    items.forEach(s=>(s.items||[]).forEach(i=>{
        const q=parseInt(i.quantity||0);if(!q)return;
        const key=i.item_name+' ('+i.unit+')';
        itemMap[key]=(itemMap[key]||0)+q;
    }));
    const itemLabels=Object.keys(itemMap);
    const itemData=itemLabels.map(k=>itemMap[k]);
    if(itemChart)itemChart.destroy();
    setChartState('itemChart','itemChartEmpty',itemLabels.length>0);
    if(itemLabels.length){
        itemChart=new Chart(document.getElementById('itemChart'),{
            type:'doughnut',
            data:{labels:itemLabels,datasets:[{data:itemData,backgroundColor:chartColors(itemLabels.length),borderWidth:2,borderColor:'#fff'}]},
            options:{maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{boxWidth:12,font:{size:11}}},tooltip:{callbacks:{label:(c)=>pctLabel(c,'qty')}}}}
        });
    }

    const total=items.length;
    const confirmed=items.filter(s=>(s.reports||[]).length>0).length;
    if(statusChart)statusChart.destroy();
    setChartState('statusChart','statusChartEmpty',total>0);
    if(total>0){
        statusChart=new Chart(document.getElementById('statusChart'),{
            type:'pie',
            data:{labels:['Confirmed','Awaiting'],datasets:[{data:[confirmed,total-confirmed],backgroundColor:['#28a745','#fd7e14'],borderWidth:2,borderColor:'#fff'}]},
            options:{maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{boxWidth:12,font:{size:11}}},tooltip:{callbacks:{label:(c)=>pctLabel(c,'schedule'+(c.parsed===1?'':'s'))}}}}
        });
    }

    const brgyMap={};
    items.forEach(s=>{
        (s.targets||[]).forEach(t=>{
            if(!brgyMap[t.barangay_id])brgyMap[t.barangay_id]={name:t.barangay_name,confirmed:0};
        });
        (s.reports||[]).forEach(r=>{
            if(!brgyMap[r.barangay_id])brgyMap[r.barangay_id]={name:r.barangay_name,confirmed:0};
            brgyMap[r.barangay_id].confirmed++;
        });
    });
    const brgy=Object.values(brgyMap);
    const brgyLabels=brgy.map(b=>b.name);
    const brgyData=brgy.map(b=>b.confirmed);
    if(brgyChart)brgyChart.destroy();
    setChartState('brgyChart','brgyChartEmpty',brgyLabels.length>0);
    if(brgyLabels.length){
        brgyChart=new Chart(document.getElementById('brgyChart'),{
            type:'bar',
            data:{labels:brgyLabels,datasets:[{label:'Receipts Confirmed',data:brgyData,backgroundColor:chartColors(brgyLabels.length),borderRadius:6,maxBarThickness:36}]},
            options:{maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:(c)=>' '+c.parsed+' confirmation'+(c.parsed===1?'':'s')}}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}
        });
    }
}

function deleteReport(id,barangayName){
    if(!confirm('Delete the confirmation report from '+barangayName+'?'))return;
    const fd=new FormData();fd.append('action','delete_report');fd.append('id',id);
    fetch('relief_distribution_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>loadSchedules());
}

function renderSchedules(items){
    const el=document.getElementById('scheduleList');
    let confirmed=0,pending=0;
    items.forEach(s=>{
        const count=(s.reports||[]).length;
        const expect=s.target_all ? s.total_households : (s.targets||[]).length;
        confirmed+=count;
        pending+=(count===0?1:0);
    });
    document.getElementById('sumSchedules').textContent=items.length;
    document.getElementById('sumConfirmed').textContent=confirmed;
    document.getElementById('sumPending').textContent=pending;

    if(!items.length){el.innerHTML='<div class="empty"><i class="fas fa-box-open"></i><p>No relief schedules yet</p></div>';return;}
    el.innerHTML=items.map(s=>{
        const d=new Date(s.distribution_date).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});
        const tm=s.distribution_time?s.distribution_time.substring(0,5):'';
        const itemsHtml=s.items.map(i=>'<span class="item-badge">'+esc(i.item_name)+' × '+i.quantity+' '+esc(i.unit)+'</span>').join('');
        const tgt=s.target_all?'All Barangays ('+s.total_households+' HH total)':
            (s.targets||[]).map(t=>t.barangay_name+' ('+t.households+' HH)').join(', ');
        const reps=(s.reports||[]).map(r=>{
            const rd=new Date(r.received_at.replace(' ','T'));
            const rdt=rd.toLocaleString('en-US',{month:'long',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
            const docs=(r.documents||[]).map(doc=>{
                const url='../barangay/'+doc.file_path;
                const ext=doc.file_path.split('.').pop().toLowerCase();
                if(ext==='pdf')return '<a class="doc-pdf" href="'+url+'" target="_blank"><i class="fas fa-file-pdf" style="font-size:1.3rem;margin-right:3px;"></i>PDF</a>';
                return '<a href="'+url+'" target="_blank"><img src="'+url+'" alt="Documentation"></a>';
            }).join('');
            return '<div class="report-card">'+
                '<div class="brgy"><i class="fas fa-home"></i> '+esc(r.barangay_name)+'</div>'+
                '<div class="rcv"><i class="fas fa-calendar-check"></i> '+rdt+'</div>'+
                '<div class="by"><i class="fas fa-user"></i> '+esc(r.confirmed_by)+' · submitted '+new Date(r.created_at.replace(' ','T')).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})+'</div>'+
                '<div class="nar">'+esc(r.narrative)+'</div>'+
                (docs?'<div class="report-docs">'+docs+'</div>':'')+
                '<div class="report-actions"><button onclick="deleteReport('+r.report_id+',\''+esc(r.barangay_name).replace(/'/g,"\\'")+'\')"><i class="fas fa-trash"></i> Delete</button></div></div>';
        }).join('');
        const reportsHtml=(s.reports||[]).length
            ?'<div class="reports-title"><i class="fas fa-clipboard-check" style="color:#28a745;"></i> Receipt Confirmations ('+(s.reports||[]).length+')</div><div class="report-grid">'+reps+'</div>'
            :'<div class="reports-section"><div class="no-reports"><i class="fas fa-hourglass-half" style="color:#fd7e14;"></i> No confirmation submitted yet by the barangay/s.</div></div>';
        const opts=['upcoming','ongoing','completed'].map(o=>'<option value="'+o+'"'+(s.status===o?' selected':'')+'>'+o.charAt(0).toUpperCase()+o.slice(1)+'</option>').join('');
        return '<div class="schedule-card">'+
            '<div class="schedule-head"><div><div class="schedule-title">'+esc(s.title)+'</div>'+
            (s.location?'<div class="schedule-meta" style="margin-bottom:0;margin-top:6px;"><span><i class="fas fa-map-marker-alt"></i> '+esc(s.location)+'</span></div>':'')+'</div>'+
            '<span class="schedule-status status-'+s.status+'">'+s.status.toUpperCase()+'</span></div>'+
            '<div class="schedule-body">'+
            (s.description?'<div class="schedule-desc">'+esc(s.description)+'</div>':'')+
            '<div class="schedule-meta"><span><i class="fas fa-calendar-day"></i> '+d+'</span>'+
            (tm?'<span><i class="fas fa-clock"></i> '+tm+'</span>':'')+'</div>'+
            (itemsHtml?'<div class="schedule-items">'+itemsHtml+'</div>':'')+
            '<div class="target-note"><i class="fas fa-bullseye"></i> '+esc(tgt)+'</div>'+
            '<div class="schedule-actions"><select onchange="updateStatus('+s.schedule_id+',this.value)">'+opts+'</select>'+
            '<button class="btn-delete" onclick="deleteSchedule('+s.schedule_id+')"><i class="fas fa-trash"></i></button></div>'+
            '</div><div class="reports-section">'+reportsHtml+'</div></div>';
    }).join('');
}

function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):'';}

loadDefaultItems();
loadSchedules();
setInterval(loadSchedules,15000);
</script>
</body>
</html>
