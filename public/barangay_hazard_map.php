<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';

$barangay_id = intval($_SESSION['barangay_id']);
$barangay_name = $_SESSION['barangay_name'] ?? '';

$detail = $conn->query("SELECT map_lat, map_lng, map_zoom, hazard_polygons, hazard_points, risk_level, hazard_map_updated_at FROM barangay_details WHERE barangay_id = $barangay_id")->fetch_assoc();

$mapData = [
    'lat' => null, 'lng' => null, 'zoom' => 14,
    'polygons' => [], 'points' => [], 'risk_level' => 'Low'
];
if ($detail) {
    $mapData['risk_level'] = $detail['risk_level'] ?? 'Low';
    if ($detail['map_lat'] !== null) {
        $mapData['lat'] = floatval($detail['map_lat']);
        $mapData['lng'] = floatval($detail['map_lng']);
        $mapData['zoom'] = intval($detail['map_zoom'] ?: 14);
        $mapData['polygons'] = json_decode($detail['hazard_polygons'] ?? '[]', true) ?: [];
        $mapData['points'] = json_decode($detail['hazard_points'] ?? '[]', true) ?: [];
        $mapData['updated_at'] = !empty($detail['hazard_map_updated_at']) ? $detail['hazard_map_updated_at'] : null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Hazard Map</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;overflow:hidden;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:16px 25px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.3rem;}
.profile-area{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.profile-area:hover{background:rgba(255,255,255,0.25);}
.profile-area img{width:36px;height:36px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}
.content{padding:16px 25px;}

.hm-info{background:white;border-radius:12px;padding:12px 16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:center;gap:10px;font-size:0.85rem;color:#555;flex-wrap:wrap;}
.hm-info i{color:#0072C6;font-size:1.1rem;}
.hm-info b{color:#333;}
.hm-last{position:absolute;top:14px;left:14px;z-index:800;background:white;border-radius:20px;box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:0.75rem;color:#666;padding:7px 14px;display:flex;align-items:center;gap:6px;}
.hm-last i{color:#0072C6;}
.hm-save{margin-left:auto;padding:9px 22px;background:#28a745;color:white;border:none;border-radius:8px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;box-shadow:0 2px 8px rgba(40,167,69,0.3);}
.hm-save-float{position:absolute;top:14px;right:14px;z-index:800;margin-left:0;}
.hm-save:hover{background:#218838;transform:translateY(-1px);}
.hm-save.saving{opacity:0.6;pointer-events:none;}
.hm-save:disabled{opacity:0.5;cursor:not-allowed;box-shadow:none;}
.hm-save:disabled:hover{background:#28a745;transform:none;}

.hm-editor{position:relative;display:flex;gap:14px;align-items:stretch;}
.hm-map{flex:1;height:calc(100vh - 108px);min-height:340px;background:#e8e8e8;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,0.12);position:relative;}

.hm-panel{width:240px;flex-shrink:0;background:white;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,0.12);padding:12px;display:flex;flex-direction:column;gap:10px;height:calc(100vh - 108px);min-height:340px;overflow-y:auto;}
.hm-toolbar{display:flex;flex-direction:column;gap:6px;}
.hm-toolbar .hm-t-label{font-size:0.68rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.5px;padding:0 4px 4px;border-bottom:1px solid #eee;margin-bottom:2px;}
.hm-tool{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;border:none;background:#f5f7fa;cursor:pointer;font-size:0.78rem;font-weight:600;color:#444;text-align:left;transition:all 0.15s;width:100%;}
.hm-tool:hover{background:#e9eef4;}
.hm-tool i{width:18px;text-align:center;font-size:0.85rem;flex-shrink:0;}
.hm-tool .hm-t-text{display:flex;flex-direction:column;align-items:flex-start;line-height:1.15;font-weight:600;color:#444;}
.hm-tool .hm-t-text small{font-weight:500;color:#999;font-size:0.66rem;margin-top:2px;}
.hm-tool.active{color:white;background:#0072C6;box-shadow:0 2px 8px rgba(0,114,198,0.35);}
.hm-tool.delete.active{background:#dc3545;box-shadow:0 2px 8px rgba(220,53,69,0.35);}
.hm-tool.locked{opacity:0.45;cursor:not-allowed;}
.hm-tool.undo:disabled{opacity:0.45;cursor:not-allowed;}
.hm-undocnt{background:#e8ecf0;border-radius:9px;padding:0 7px;font-size:0.68rem;color:#555;margin-left:auto;}
.hm-tool .sw{width:12px;height:12px;border-radius:3px;margin-left:auto;flex-shrink:0;}

.hm-hint{position:absolute;bottom:14px;left:50%;transform:translateX(-50%);z-index:800;background:rgba(0,0,0,0.75);color:#fff;font-size:0.78rem;padding:8px 18px;border-radius:20px;display:none;white-space:nowrap;pointer-events:none;}

.hm-legend{margin-top:auto;background:#f9fafb;border-radius:10px;border:1px solid #eee;padding:10px 14px;font-size:0.75rem;}
.hm-risk{background:#fff;border:1px solid #eee;border-radius:10px;padding:10px 12px;}
.hm-risk .hm-t-label{margin-bottom:6px;}
.hm-risk-opts{display:grid;grid-template-columns:1fr 1fr;gap:6px;}
.hm-risk-opt{padding:7px 6px;border-radius:8px;border:1.5px solid #e0e0e0;background:#fff;font-size:0.72rem;font-weight:700;cursor:pointer;color:#555;transition:all 0.2s;font-family:'Segoe UI',sans-serif;}
.hm-risk-opt:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,0.12);}
.hm-risk-opt.r-low.selected{background:#28a745;border-color:#28a745;color:#fff;}
.hm-risk-opt.r-medium.selected{background:#fd7e14;border-color:#fd7e14;color:#fff;}
.hm-risk-opt.r-high.selected{background:#dc3545;border-color:#dc3545;color:#fff;}
.hm-risk-opt.r-critical.selected{background:#7b1a1a;border-color:#7b1a1a;color:#fff;animation:hmRiskPulse 1.6s infinite;}
@keyframes hmRiskPulse{0%,100%{opacity:1;}50%{opacity:.6;}}
.hm-risk-note{font-size:0.66rem;color:#999;margin-top:6px;line-height:1.3;}
.hm-risk-note i{color:#0072C6;}
.hm-legend h5{font-size:0.7rem;color:#888;text-transform:uppercase;margin-bottom:6px;letter-spacing:0.5px;}
.hm-legend .lg-item{display:flex;align-items:center;gap:8px;margin:3px 0;color:#555;}
.hm-legend .lg-ico{width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;font-size:7px;color:#fff;}
.hm-legend .lg-poly{width:18px;height:10px;border-radius:2px;display:inline-block;}
.hm-legend .lg-line{width:22px;height:4px;border-radius:2px;display:inline-block;}

.hm-toast{position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:13px 22px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:3000;display:none;align-items:center;gap:10px;font-weight:600;font-size:0.85rem;}
.hm-toast.show{display:flex;animation:hmIn 0.3s ease;}
@keyframes hmIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}

.hm-modal{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:3000;display:none;align-items:center;justify-content:center;}
.hm-modal.show{display:flex;}
.hm-modal-box{background:white;border-radius:14px;padding:18px 20px;width:320px;box-shadow:0 8px 30px rgba(0,0,0,0.25);}
.hm-modal-box h5{margin-bottom:14px;color:#333;font-size:0.95rem;display:flex;align-items:center;gap:8px;}
.hm-modal-box h5 i{color:#dc3545;}
.hm-modal-box label{font-size:0.75rem;color:#777;font-weight:600;display:block;margin:10px 0 4px;}
.hm-modal-box select,.hm-modal-box input{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:0.85rem;}
.hm-modal-box input:focus,.hm-modal-box select:focus{outline:none;border-color:#dc3545;}
.hm-modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;}
.hm-modal-actions button{padding:8px 16px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.82rem;}
.hm-btn-cancel{background:#e9ecef;color:#555;}
.hm-btn-add{background:#dc3545;color:white;}
.hm-btn-add:hover{background:#c82333;}

.hm-divicon{background:transparent;border:none;}
.hm-ico{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,0.35);border:2px solid #fff;}

.leaflet-draw-toolbar a{display:none;}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-map-marked-alt"></i> Barangay Hazard Map</h1>
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
            <div class="hm-editor">
                <div class="hm-map" id="hazardMap">
                    <div class="hm-last" id="hmLastSaved"><i class="fas fa-clock"></i> Not saved yet</div>
                    <button type="button" class="hm-save hm-save-float" id="saveBtn" onclick="saveMap()"><i class="fas fa-save"></i> Save Hazard Map</button>
                    <div class="hm-hint" id="hmHint"></div>
                </div>

                <div class="hm-panel">
                    <div class="hm-toolbar">
                        <div class="hm-t-label">Crop Barangay Area</div>
                        <button type="button" class="hm-tool" data-tool="boxcrop" data-boundary="1" onclick="setTool('boxcrop')"><i class="fas fa-square"></i><span class="hm-t-text"><b>Box</b><small>drag a rectangle</small></span></button>
                        <button type="button" class="hm-tool" data-tool="freehand" data-boundary="1" onclick="setTool('freehand')"><i class="fas fa-pen"></i><span class="hm-t-text"><b>Freehand</b><small>hold &amp; trace around</small></span></button>
                        <button type="button" class="hm-tool" data-tool="boundary" data-boundary="1" onclick="setTool('boundary')"><i class="fas fa-draw-polygon"></i><span class="hm-t-text"><b>Points</b><small>click corner by corner</small></span></button>
                        <div class="hm-t-label" style="margin-top:4px;">Hazards &amp; Facilities</div>
                        <button type="button" class="hm-tool" data-tool="prone" onclick="setTool('prone')"><i class="fas fa-triangle-exclamation"></i> Prone Area <span class="sw" style="background:#dc3545;"></span></button>
                        <button type="button" class="hm-tool" data-tool="evac" onclick="setTool('evac')"><i class="fas fa-hospital"></i> Evacuation Center <span class="sw" style="background:#0072C6;"></span></button>
                        <button type="button" class="hm-tool" data-tool="hall" onclick="setTool('hall')"><i class="fas fa-university"></i> Barangay Hall <span class="sw" style="background:#e8710a;"></span></button>
                        <button type="button" class="hm-tool" data-tool="school" onclick="setTool('school')"><i class="fas fa-school"></i> School <span class="sw" style="background:#28a745;"></span></button>
                        <div class="hm-t-label" style="margin-top:4px;">Edit</div>
                        <button type="button" class="hm-tool undo" id="undoBtn" onclick="doUndo()"><i class="fas fa-undo"></i> Undo <span class="hm-undocnt" id="undoCnt">0</span></button>
                        <button type="button" class="hm-tool delete" data-tool="delete" onclick="setTool('delete')"><i class="fas fa-trash-alt"></i> Delete</button>
                    </div>

                    <div class="hm-risk">
                        <div class="hm-t-label"><i class="fas fa-shield-alt"></i> Barangay Risk Level</div>
                        <div class="hm-risk-opts">
                            <button type="button" class="hm-risk-opt r-low" data-risk="Low">Low</button>
                            <button type="button" class="hm-risk-opt r-medium" data-risk="Medium">Medium</button>
                            <button type="button" class="hm-risk-opt r-high" data-risk="High">High</button>
                            <button type="button" class="hm-risk-opt r-critical" data-risk="Critical">Critical</button>
                        </div>
                        <div class="hm-risk-note"><i class="fas fa-info-circle"></i> Your risk level shows on your dashboard and to the admin.</div>
                    </div>

                    <div class="hm-legend">
                        <h5>Legend</h5>
                        <div class="lg-item"><span class="lg-poly" style="background:rgba(0,114,198,0.35);border:2px solid #0072C6;"></span> Barangay Boundary</div>
                        <div class="lg-item"><span class="lg-line" style="background:#dc3545;"></span> Prone Area</div>
                        <div class="lg-item"><span class="lg-ico" style="background:#0072C6;"><i class="fas fa-hospital"></i></span> Evacuation Center</div>
                        <div class="lg-item"><span class="lg-ico" style="background:#e8710a;"><i class="fas fa-university"></i></span> Barangay Hall</div>
                        <div class="lg-item"><span class="lg-ico" style="background:#28a745;"><i class="fas fa-school"></i></span> School</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="hm-toast" id="hmToast"><i class="fas fa-check-circle"></i> Hazard map saved!</div>

<div class="hm-modal" id="proneModal">
    <div class="hm-modal-box">
        <h5><i class="fas fa-triangle-exclamation"></i> Add Prone Area</h5>
        <label>Type</label>
        <select id="proneType">
            <option value="Flood">Flood</option>
            <option value="Landslide">Landslide</option>
            <option value="Earthquake">Earthquake</option>
            <option value="Fire">Fire</option>
            <option value="Storm">Storm</option>
            <option value="Erosion">Erosion</option>
            <option value="Other">Other</option>
        </select>
        <label>Name</label>
        <input type="text" id="proneName" placeholder="e.g. River flood zone" maxlength="60">
        <div class="hm-modal-actions">
            <button type="button" class="hm-btn-cancel" onclick="closeProneModal()">Cancel</button>
            <button type="button" class="hm-btn-add" onclick="confirmProne()"><i class="fas fa-plus"></i> Add</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
const MAPDATA = <?php echo json_encode($mapData); ?>;

const TOOLS = {
    boundary: { label: 'Barangay Boundary', icon: 'fa-map-marker-alt', color: '#0072C6', shape: 'polygon' },
    boxcrop:  { label: 'Barangay Boundary', icon: 'fa-square', color: '#0072C6', shape: 'box' },
    freehand: { label: 'Barangay Boundary', icon: 'fa-pen', color: '#0072C6', shape: 'freehand' },
    prone:    { label: 'Prone Area',         icon: 'fa-triangle-exclamation', color: '#dc3545', shape: 'line' },
    evac:     { label: 'Evacuation Center',  icon: 'fa-hospital', color: '#0072C6', shape: 'marker' },
    hall:     { label: 'Barangay Hall',      icon: 'fa-university', color: '#e8710a', shape: 'marker' },
    school:   { label: 'School',             icon: 'fa-school', color: '#28a745', shape: 'marker' }
};

const DEFAULT_CENTER = [13.3189, 123.7383];
let map, drawHandler = null, currentTool = null, deleteMode = false;
let polygons = [], markers = [];
let undoStack = [];
let freehand = null;
let dirty = false;
let pendingProneLayer = null;

function markerIcon(color, icon){
    return L.divIcon({
        className: 'hm-divicon',
        html: '<div class="hm-ico" style="background:' + color + '"><i class="fas ' + icon + '"></i></div>',
        iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -16]
    });
}

function initMap(){
    const lat = MAPDATA.lat !== null ? MAPDATA.lat : DEFAULT_CENTER[0];
    const lng = MAPDATA.lng !== null ? MAPDATA.lng : DEFAULT_CENTER[1];
    const zoom = MAPDATA.lat !== null ? (MAPDATA.zoom || 14) : 13;

    map = L.map('hazardMap', { zoomControl: true }).setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20, attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    loadLayers();
}

function loadLayers(){
    (MAPDATA.polygons || []).forEach(p => {
        const isLine = p.type === 'line';
        const layer = isLine
            ? L.polyline(p.points, { color: p.color || '#0072C6', weight: 6, opacity: 0.95 })
            : L.polygon(p.points, { color: p.color || '#0072C6', fillColor: p.color || '#0072C6', fillOpacity: 0.25, weight: 3 });
        layer.addTo(map);
        const sub = p.kind === 'prone' ? 'Prone Area' + (p.ptype ? ': ' + p.ptype : '') : 'Barangay Boundary';
        layer.bindPopup('<b>' + esc(p.name || TOOLS[p.kind]?.label || 'Area') + '</b><br><small>' + sub + '</small>');
        attachLayer(layer, p.id);
        polygons.push({ id: p.id, kind: p.kind, name: p.name, ptype: p.ptype, layer });
    });
    (MAPDATA.points || []).forEach(m => {
        const meta = TOOLS[m.kind] || TOOLS.evac;
        const layer = L.marker([m.lat, m.lng], { icon: markerIcon(m.color || meta.color, meta.icon) });
        layer.addTo(map);
        layer.bindPopup('<b>' + esc(m.name || meta.label) + '</b><br><small>' + meta.label + '</small>');
        attachLayer(layer, m.id);
        markers.push({ id: m.id, kind: m.kind, name: m.name, layer });
    });

    const boundary = polygons.find(p => p.kind === 'boundary');
    if (boundary) applyViewLock();

    setLastSaved(MAPDATA.updated_at || null);
    updateStep();
    updateUndoBtn();
}

function applyViewLock(){
    const boundary = polygons.find(p => p.kind === 'boundary');
    if (!boundary) return;
    const b = boundary.layer.getBounds();
    if (!b.isValid()) return;
    map.fitBounds(b, { padding: [40, 40] });
    map.on('zoomend', keepCentered);
}

function keepCentered(){
    if (currentTool || deleteMode || (freehand && freehand.drawing)) return;
    const boundary = polygons.find(p => p.kind === 'boundary');
    if (!boundary) return;
    map.setView(boundary.layer.getBounds().getCenter(), map.getZoom(), { animate: false });
}

function hasBoundary(){ return polygons.some(p => p.kind === 'boundary'); }

function updateStep(){
    const hasB = hasBoundary();
    document.querySelectorAll('.hm-tool[data-tool]:not([data-boundary])').forEach(b => {
        b.disabled = !hasB;
        b.classList.toggle('locked', !hasB);
    });
    document.getElementById('saveBtn').disabled = !hasB;
}

function updateUndoBtn(){
    const btn = document.getElementById('undoBtn');
    const cnt = undoStack.length;
    btn.disabled = cnt === 0;
    document.getElementById('undoCnt').textContent = cnt;
}

function doUndo(){
    cancelDraw();
    const a = undoStack.pop();
    if (!a) return;
    if (a.type === 'add_polygon') {
        map.removeLayer(a.layer);
        polygons.splice(a.index, 1);
    } else if (a.type === 'add_marker') {
        map.removeLayer(a.layer);
        markers.splice(a.index, 1);
    } else if (a.type === 'del_polygon') {
        a.layer.addTo(map);
        attachLayer(a.layer, a.obj.id);
        polygons.splice(a.index, 0, a.obj);
    } else if (a.type === 'del_marker') {
        a.layer.addTo(map);
        attachLayer(a.layer, a.obj.id);
        markers.splice(a.index, 0, a.obj);
    } else if (a.type === 'boundary_replace') {
        map.removeLayer(a.newLayer);
        polygons = polygons.filter(p => p.layer !== a.newLayer);
        a.oldLayer.addTo(map);
        attachLayer(a.oldLayer, a.oldObj.id);
        polygons.splice(a.oldIndex, 0, a.oldObj);
    }
    updateStep();
    updateUndoBtn();
    hint('Undo: last change reversed.');
    dirty = true;
}

function setLastSaved(ts){
    const el = document.getElementById('hmLastSaved');
    if (!ts) { el.innerHTML = '<i class="fas fa-clock"></i> Not saved yet'; return; }
    el.innerHTML = '<i class="fas fa-clock"></i> Last saved: ' + fmtTs(ts);
}

function fmtTs(s){
    const d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d)) return s;
    return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function setTool(tool){
    cancelDraw();

    const btn = document.querySelector('.hm-tool[data-tool="' + tool + '"]');
    const wasActive = btn && btn.classList.contains('active');
    document.querySelectorAll('.hm-tool').forEach(b => b.classList.remove('active'));
    if (wasActive) { hint(''); return; }

    if (tool === 'delete') {
        btn.classList.add('active');
        deleteMode = true;
        hint('Delete mode: click a marker or area to remove it');
        return;
    }

    const meta = TOOLS[tool];
    currentTool = tool;
    if (btn) btn.classList.add('active');

    if (meta.shape === 'freehand') {
        freehand = { drawing: false, pts: [], line: null };
        map.dragging.disable();
        map.doubleClickZoom.disable();
        map.on('mousedown', onFreehandStart);
        map.on('mousemove', onFreehandMove);
        map.on('mouseup', onFreehandEnd);
        document.addEventListener('mouseup', onFreehandEnd);
        hint('Hold the mouse button, trace around your barangay, then release');
        return;
    }

    if (meta.shape === 'box') {
        drawHandler = new L.Draw.Rectangle(map, {
            shapeOptions: { color: meta.color, fillColor: meta.color, fillOpacity: 0.25, weight: 3 }
        });
    } else if (meta.shape === 'polygon') {
        drawHandler = new L.Draw.Polygon(map, {
            allowIntersection: false,
            showArea: true,
            shapeOptions: { color: meta.color, fillColor: meta.color, fillOpacity: 0.25, weight: 3 }
        });
    } else if (meta.shape === 'line') {
        drawHandler = new L.Draw.Polyline(map, {
            shapeOptions: { color: meta.color, weight: 6, opacity: 0.95 }
        });
    } else {
        drawHandler = new L.Draw.Marker(map, { icon: markerIcon(meta.color, meta.icon) });
    }
    drawHandler.enable();
    map.once('draw:created', onCreated);
    if (meta.shape === 'box') {
        hint('Drag on the map to draw a box around your barangay area');
    } else if (meta.shape === 'polygon') {
        hint('Click corner points, then click the first point to finish');
    } else if (meta.shape === 'line') {
        hint('Click points along the prone area, then click the last point to finish');
    } else {
        hint('Click on the map to place the ' + meta.label.toLowerCase());
    }
}

function onCreated(e){
    const layer = e.layer;
    const tool = currentTool;

    if (tool === 'boundary' || tool === 'boxcrop') {
        commitBoundary(layer, MAPDATA.barangay_name || 'Barangay Boundary');
        return;
    }

    const meta = TOOLS[tool];

    if (tool === 'prone') {
        pendingProneLayer = layer;
        document.getElementById('proneType').value = 'Flood';
        document.getElementById('proneName').value = '';
        document.getElementById('proneModal').classList.add('show');
        return;
    }

    let name = '';
    name = prompt(meta.label + ' name:', meta.label);
    if (name === null || name.trim() === '') {
        map.removeLayer(layer);
        resetTool();
        return;
    }

    if (meta.shape === 'line') {
        undoStack.push({ type: 'add_polygon', layer: layer, index: polygons.length });
        layer.setStyle({ color: meta.color, weight: 6 });
    } else if (meta.shape === 'polygon') {
        undoStack.push({ type: 'add_polygon', layer: layer, index: polygons.length });
        layer.setStyle({ color: meta.color, fillColor: meta.color });
    } else {
        undoStack.push({ type: 'add_marker', layer: layer, index: markers.length });
        layer.setIcon(markerIcon(meta.color, meta.icon));
    }

    const id = Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    layer.bindPopup('<b>' + esc(name) + '</b><br><small>' + meta.label + '</small>');
    attachLayer(layer, id);
    layer.addTo(map);
    if (meta.shape === 'marker') {
        markers.push({ id: id, kind: tool, name: name, layer });
    } else {
        polygons.push({ id: id, kind: tool, name: name, layer });
    }
    updateStep();
    updateUndoBtn();
    resetTool();
    dirty = true;
}

function confirmProne(){
    const layer = pendingProneLayer;
    if (!layer) return;
    const ptype = document.getElementById('proneType').value;
    let name = document.getElementById('proneName').value.trim();
    if (!name) name = ptype;
    closeProneModal();
    addPolylineLayer(layer, 'prone', name, ptype);
}

function closeProneModal(){
    if (pendingProneLayer) { map.removeLayer(pendingProneLayer); pendingProneLayer = null; }
    document.getElementById('proneModal').classList.remove('show');
    resetTool();
}

function addPolylineLayer(layer, tool, name, ptype){
    const meta = TOOLS[tool];
    undoStack.push({ type: 'add_polygon', layer: layer, index: polygons.length });
    layer.setStyle({ color: meta.color, weight: 6, opacity: 0.95 });
    layer.addTo(map);
    const id = Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    layer.bindPopup('<b>' + esc(name) + '</b><br><small>' + meta.label + ': ' + esc(ptype) + '</small>');
    attachLayer(layer, id);
    polygons.push({ id: id, kind: tool, name: name, ptype: ptype, layer });
    updateStep();
    updateUndoBtn();
    resetTool();
    dirty = true;
}

function commitBoundary(layer, name){
    const meta = TOOLS.boundary;
    const old = polygons.find(p => p.kind === 'boundary');
    if (old) {
        const oldIndex = polygons.indexOf(old);
        undoStack.push({ type: 'boundary_replace', oldLayer: old.layer, oldObj: old, oldIndex: oldIndex, newLayer: layer });
        map.removeLayer(old.layer);
        polygons = polygons.filter(p => p !== old);
    } else {
        undoStack.push({ type: 'add_polygon', layer: layer, index: polygons.length });
    }
    layer.setStyle({ color: meta.color, fillColor: meta.color });
    layer.addTo(map);
    const id = Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    layer.bindPopup('<b>' + esc(name) + '</b><br><small>Barangay Boundary</small>');
    attachLayer(layer, id);
    polygons.push({ id: id, kind: 'boundary', name: name, layer });
    applyViewLock();
    updateStep();
    updateUndoBtn();
    resetTool();
    dirty = true;
}

function onFreehandStart(e){
    if (!freehand || freehand.drawing) return;
    if (e.originalEvent && e.originalEvent.button !== undefined && e.originalEvent.button !== 0) return;
    freehand.drawing = true;
    freehand.pts = [];
    freehand.line = L.polygon([], { color: '#0072C6', fillColor: '#0072C6', fillOpacity: 0.25, weight: 3, dashArray: '6 3' });
    freehand.line.addTo(map);
    document.getElementById('hazardMap').style.cursor = 'crosshair';
}

function onFreehandMove(e){
    if (!freehand || !freehand.drawing) return;
    freehand.pts.push([e.latlng.lat, e.latlng.lng]);
    if (freehand.pts.length > 1) freehand.line.setLatLngs([freehand.pts]);
}

function onFreehandEnd(e){
    if (!freehand || !freehand.drawing) return;
    if (e.originalEvent && e.originalEvent.button !== undefined && e.originalEvent.button !== 0) return;
    freehand.drawing = false;
    if (e.latlng) freehand.pts.push([e.latlng.lat, e.latlng.lng]);
    if (freehand.line) { map.removeLayer(freehand.line); freehand.line = null; }
    document.getElementById('hazardMap').style.cursor = '';
    const pts = freehand.pts.slice();
    if (pts.length < 3) { hint('Trace too small — press Freehand and try again.'); return; }
    const layer = L.polygon(pts, { color: '#0072C6', fillColor: '#0072C6', fillOpacity: 0.25, weight: 3 });
    layer.addTo(map);
    commitBoundary(layer, MAPDATA.barangay_name || 'Barangay Boundary');
}

function attachLayer(layer, id){
    layer.on('click', function(){
        if (!deleteMode) return;
        const pi = polygons.findIndex(p => p.id === id);
        if (pi >= 0) {
            undoStack.push({ type: 'del_polygon', layer: polygons[pi].layer, index: pi, obj: polygons[pi] });
            map.removeLayer(polygons[pi].layer);
            polygons.splice(pi, 1);
            updateStep(); updateUndoBtn();
            dirty = true;
            return;
        }
        const mi = markers.findIndex(m => m.id === id);
        if (mi >= 0) {
            undoStack.push({ type: 'del_marker', layer: markers[mi].layer, index: mi, obj: markers[mi] });
            map.removeLayer(markers[mi].layer);
            markers.splice(mi, 1);
            updateStep(); updateUndoBtn();
            dirty = true;
        }
    });
}

function cancelDraw(){
    if (freehand) {
        map.off('mousedown', onFreehandStart);
        map.off('mousemove', onFreehandMove);
        map.off('mouseup', onFreehandEnd);
        document.removeEventListener('mouseup', onFreehandEnd);
        if (freehand.line) map.removeLayer(freehand.line);
        freehand = null;
        map.dragging.enable();
        map.doubleClickZoom.enable();
        document.getElementById('hazardMap').style.cursor = '';
    }
    if (drawHandler) { drawHandler.disable(); drawHandler = null; }
    if (currentTool) currentTool = null;
    deleteMode = false;
    document.querySelectorAll('.hm-tool').forEach(b => b.classList.remove('active'));
    hint('');
}

function resetTool(){
    cancelDraw();
    hint('Saved to map. You can keep drawing or click Save Hazard Map.');
}

function hint(msg){
    const el = document.getElementById('hmHint');
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg;
}

function showToast(msg){
    const t = document.getElementById('hmToast');
    t.innerHTML = '<i class="fas fa-check-circle"></i> ' + msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

let currentRisk = MAPDATA.risk_level || 'Low';
function initRisk(){
    document.querySelectorAll('.hm-risk-opt').forEach(b => {
        b.classList.toggle('selected', b.dataset.risk === currentRisk);
        b.addEventListener('click', function(){
            if (b.dataset.risk === currentRisk) return;
            setRisk(b.dataset.risk);
        });
    });
}
function setRisk(r){
    const prev = currentRisk;
    currentRisk = r;
    document.querySelectorAll('.hm-risk-opt').forEach(b => b.classList.toggle('selected', b.dataset.risk === currentRisk));
    const fd = new FormData();
    fd.append('action', 'set_risk');
    fd.append('risk_level', r);
    fetch('hazard_map_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                currentRisk = prev;
                document.querySelectorAll('.hm-risk-opt').forEach(b => b.classList.toggle('selected', b.dataset.risk === currentRisk));
                alert('Failed to update risk level: ' + (res.error || 'Unknown error'));
            }
        })
        .catch(() => { currentRisk = prev; document.querySelectorAll('.hm-risk-opt').forEach(b => b.classList.toggle('selected', b.dataset.risk === currentRisk)); alert('Failed to update risk level.'); });
}

function toPoints(layer){
    if (layer instanceof L.Polygon) {
        const ll = layer.getLatLngs();
        const rings = Array.isArray(ll) ? ll : [ll];
        const ring = rings[0] || [];
        return ring.map(p => [p.lat, p.lng]);
    }
    return layer.getLatLngs().map(p => [p.lat, p.lng]);
}

function saveMap(){
    const btn = document.getElementById('saveBtn');
    btn.classList.add('saving');

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('map_lat', map.getCenter().lat.toFixed(6));
    fd.append('map_lng', map.getCenter().lng.toFixed(6));
    fd.append('map_zoom', map.getZoom());
    fd.append('hazard_polygons', JSON.stringify(polygons.map(p => ({ id: p.id, kind: p.kind, name: p.name, ptype: p.ptype || null, color: TOOLS[p.kind].color, type: p.layer instanceof L.Polygon ? 'polygon' : 'line', points: toPoints(p.layer) }))));
    fd.append('hazard_points', JSON.stringify(markers.map(m => ({ id: m.id, kind: m.kind, name: m.name, color: TOOLS[m.kind].color, lat: m.layer.getLatLng().lat, lng: m.layer.getLatLng().lng }))));

    fetch('hazard_map_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.classList.remove('saving');
            if (res.success) {
                document.getElementById('hmToast').classList.add('show');
                setTimeout(() => document.getElementById('hmToast').classList.remove('show'), 3000);
                setLastSaved(res.updated_at || new Date().toISOString());
                cancelDraw();
                dirty = false;
            } else {
                alert('Save failed: ' + (res.error || 'Unknown error'));
            }
        })
        .catch(() => { btn.classList.remove('saving'); alert('Save failed. Check your connection.'); });
}

function esc(t){ if (t === null || t === undefined) return ''; const d = document.createElement('div'); d.appendChild(document.createTextNode(String(t))); return d.innerHTML; }

function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.profile-area')) document.getElementById("dropdownMenu").classList.remove("show"); };

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
        if (document.getElementById('proneModal').classList.contains('show')) { closeProneModal(); return; }
        cancelDraw();
    }
    if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z')) { e.preventDefault(); doUndo(); }
});
window.addEventListener('beforeunload', function(e){
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
});
document.addEventListener('DOMContentLoaded', initMap);
document.addEventListener('DOMContentLoaded', initRisk);
</script>
</body>
</html>
