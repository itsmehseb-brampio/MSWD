<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';

$barangay_id = intval($_SESSION['barangay_id']);
$barangay_name = $_SESSION['barangay_name'] ?? '';

$detail = $conn->query("SELECT map_lat, map_lng, map_zoom, hazard_polygons, hazard_points, hazard_map_updated_at, hazard_map FROM barangay_details WHERE barangay_id = $barangay_id")->fetch_assoc();

$mapData = [
    'lat' => null, 'lng' => null, 'zoom' => 14,
    'polygons' => [], 'points' => [], 'updated_at' => null
];
$hasLive = false;
if ($detail && $detail['map_lat'] !== null) {
    $hasLive = true;
    $mapData['lat'] = floatval($detail['map_lat']);
    $mapData['lng'] = floatval($detail['map_lng']);
    $mapData['zoom'] = intval($detail['map_zoom'] ?: 14);
    $mapData['polygons'] = json_decode($detail['hazard_polygons'] ?? '[]', true) ?: [];
    $mapData['points'] = json_decode($detail['hazard_points'] ?? '[]', true) ?: [];
    $mapData['updated_at'] = !empty($detail['hazard_map_updated_at']) ? $detail['hazard_map_updated_at'] : null;
}
$hazard_image = ($detail && !empty($detail['hazard_map'])) ? $detail['hazard_map'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hazard Map View</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
.content{padding:16px 25px;position:relative;}

.hm-note{position:absolute;top:30px;right:39px;z-index:800;background:rgba(255,255,255,0.95);border:1px solid #ffe082;border-radius:20px;box-shadow:0 2px 8px rgba(0,0,0,0.15);padding:7px 14px;font-size:0.75rem;color:#666;display:flex;align-items:center;gap:6px;}
.hm-note i{color:#e8710a;}
.hm-note b{color:#6b4f00;}

.hm-editor{position:relative;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.12);}
.hm-map{width:100%;height:calc(100vh - 108px);min-height:340px;background:#e8e8e8;}

.hm-legend{position:absolute;bottom:14px;right:14px;z-index:500;background:white;border-radius:10px;box-shadow:0 3px 12px rgba(0,0,0,0.15);padding:10px 14px;font-size:0.75rem;}
.hm-legend h5{font-size:0.7rem;color:#888;text-transform:uppercase;margin-bottom:6px;letter-spacing:0.5px;}
.hm-legend .lg-item{display:flex;align-items:center;gap:8px;margin:3px 0;color:#555;}
.hm-legend .lg-ico{width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;font-size:7px;color:#fff;}
.hm-legend .lg-poly{width:18px;height:10px;border-radius:2px;display:inline-block;}
.hm-legend .lg-line{width:22px;height:4px;border-radius:2px;display:inline-block;}

.hm-placeholder{width:100%;height:calc(100vh - 108px);min-height:340px;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 4px 18px rgba(0,0,0,0.12);display:flex;align-items:center;justify-content:center;padding:24px;text-align:center;position:relative;}
.hm-placeholder img{max-width:100%;max-height:100%;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.12);}
.hm-placeholder .no-map{color:#999;}
.hm-placeholder .no-map i{font-size:2.5rem;margin-bottom:8px;display:block;}
.hm-placeholder .no-map a{color:#0072C6;font-weight:600;}

.hm-divicon{background:transparent;border:none;}
.hm-ico{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,0.35);border:2px solid #fff;}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-map-marked-alt"></i> Hazard Map View</h1>
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
            <div class="hm-note"><i class="fas fa-clock"></i> <?php if ($mapData['updated_at']): ?>Last saved <b><?php echo htmlspecialchars($mapData['updated_at']); ?></b><?php else: ?>Not saved yet<?php endif; ?> &middot; &copy; OpenStreetMap contributors</div>

            <?php if ($hasLive): ?>
            <div class="hm-editor">
                <div class="hm-map" id="hazardMap"></div>
                <div class="hm-legend">
                    <h5>Legend</h5>
                    <div class="lg-item"><span class="lg-poly" style="background:rgba(0,114,198,0.35);border:2px solid #0072C6;"></span> Barangay Boundary</div>
                    <div class="lg-item"><span class="lg-line" style="background:#dc3545;"></span> Prone Area</div>
                    <div class="lg-item"><span class="lg-ico" style="background:#0072C6;"><i class="fas fa-hospital"></i></span> Evacuation Center</div>
                    <div class="lg-item"><span class="lg-ico" style="background:#e8710a;"><i class="fas fa-university"></i></span> Barangay Hall</div>
                    <div class="lg-item"><span class="lg-ico" style="background:#28a745;"><i class="fas fa-school"></i></span> School</div>
                </div>
            </div>
            <?php elseif (!empty($hazard_image)): ?>
            <div class="hm-placeholder">
                <img src="<?php echo htmlspecialchars($hazard_image); ?>" alt="Hazard Map">
            </div>
            <?php else: ?>
            <div class="hm-placeholder">
                <div class="no-map">
                    <i class="fas fa-map"></i>
                    <p>No hazard map yet. <a href="barangay_hazard_map.php">Edit Hazard Map</a> to create one.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const MAPDATA = <?php echo json_encode($mapData); ?>;

const VIEW_META = {
    boundary: { label: 'Barangay Boundary', icon: 'fa-map-marker-alt', color: '#0072C6' },
    prone:    { label: 'Prone Area', icon: 'fa-triangle-exclamation', color: '#dc3545' },
    evac:     { label: 'Evacuation Center', icon: 'fa-hospital', color: '#0072C6' },
    hall:     { label: 'Barangay Hall', icon: 'fa-university', color: '#e8710a' },
    school:   { label: 'School', icon: 'fa-school', color: '#28a745' }
};

let map;

function markerIcon(color, icon){
    return L.divIcon({
        className: 'hm-divicon',
        html: '<div class="hm-ico" style="background:' + color + '"><i class="fas ' + icon + '"></i></div>',
        iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -16]
    });
}

function initMap(){
    if (!document.getElementById('hazardMap')) return;
    const lat = MAPDATA.lat !== null ? MAPDATA.lat : 13.3189;
    const lng = MAPDATA.lng !== null ? MAPDATA.lng : 123.7383;
    const zoom = MAPDATA.lat !== null ? (MAPDATA.zoom || 14) : 13;

    map = L.map('hazardMap', { zoomControl: true }).setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20, attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    (MAPDATA.polygons || []).forEach(p => {
        if (!p.points || !p.points.length) return;
        const meta = VIEW_META[p.kind] || VIEW_META.prone;
        const layer = (p.type === 'line')
            ? L.polyline(p.points, { color: p.color || meta.color, weight: 6, opacity: 0.95 })
            : L.polygon(p.points, { color: p.color || meta.color, fillColor: p.color || meta.color, fillOpacity: 0.25, weight: 3 });
        layer.addTo(map);
        const sub = p.kind === 'prone' ? 'Prone Area' + (p.ptype ? ': ' + p.ptype : '') : meta.label;
        layer.bindPopup('<b>' + esc(p.name || meta.label) + '</b><br><small>' + sub + '</small>');
    });
    (MAPDATA.points || []).forEach(m => {
        const meta = VIEW_META[m.kind] || VIEW_META.evac;
        const layer = L.marker([parseFloat(m.lat), parseFloat(m.lng)], { icon: markerIcon(m.color || meta.color, meta.icon) });
        layer.addTo(map);
        layer.bindPopup('<b>' + esc(m.name || meta.label) + '</b><br><small>' + meta.label + '</small>');
    });

    const boundary = (MAPDATA.polygons || []).find(p => p.kind === 'boundary' && p.points && p.points.length);
    if (boundary) {
        const b = L.polygon(boundary.points).getBounds();
        if (b.isValid()) map.fitBounds(b, { padding: [40, 40] });
        map.on('zoomend', keepCentered);
    }
}

function keepCentered(){
    const boundary = (MAPDATA.polygons || []).find(p => p.kind === 'boundary' && p.points && p.points.length);
    if (!boundary) return;
    const b = L.polygon(boundary.points).getBounds();
    if (!b.isValid()) return;
    map.setView(b.getCenter(), map.getZoom(), { animate: false });
}

function esc(t){ if (t === null || t === undefined) return ''; const d = document.createElement('div'); d.appendChild(document.createTextNode(String(t))); return d.innerHTML; }

function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.profile-area')) document.getElementById("dropdownMenu").classList.remove("show"); };

document.addEventListener('DOMContentLoaded', initMap);
</script>
</body>
</html>
