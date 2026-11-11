@extends('layouts.admin')

@section('title', 'Malilipot Hazard Map')
@section('headerTitle', 'Malilipot Municipality Hazard Map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.hm-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.hm-toolbar .hm-label { font-size: 0.85rem; font-weight: 700; color: #555; margin-right: 4px; }
.hm-btn {
    border: 1.5px solid #d5ded9;
    background: #fff;
    border-radius: 8px;
    padding: 8px 13px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #444;
    cursor: pointer;
    transition: all 0.15s;
}
.hm-btn:hover { border-color: #11998e; color: #11998e; }
.hm-btn.active { background: #11998e; border-color: #11998e; color: #fff; }
.hm-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.hm-facility-btn {
    border: 1.5px solid #d5ded9;
    background: #fff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #444;
    cursor: pointer;
    transition: all 0.15s;
    margin-right: 4px;
}
.hm-facility-btn:hover { border-color: #11998e; color: #11998e; }
.hm-facility-btn.active { background: #11998e; border-color: #11998e; color: #fff; }
.hm-facility-btn.police { border-color: #C0392B; color: #C0392B; }
.hm-facility-btn.police.active { background: #C0392B; color: #fff; }
.hm-facility-btn.fire { border-color: #E67E22; color: #E67E22; }
.hm-facility-btn.fire.active { background: #E67E22; color: #fff; }
.hm-facility-btn.hall { border-color: #2C3E50; color: #2C3E50; }
.hm-facility-btn.hall.active { background: #2C3E50; color: #fff; }
.hm-actions { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
.hm-save {
    background: linear-gradient(90deg, #11998e, #0b6e4f);
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 9px 22px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
}
.hm-save:disabled { opacity: 0.5; cursor: not-allowed; }
#map { width: 100%; height: 520px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.hm-panel {
    background: #fff;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 16px;
}
.hm-panel h3 { font-size: 0.95rem; color: #333; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.hm-panel h3 i { color: #11998e; }
.hm-row { display: grid; grid-template-columns: 250px 1fr; gap: 18px; align-items: start; }
@media (max-width: 900px) { .hm-row { grid-template-columns: 1fr; } }
.risk-dots { display: flex; gap: 10px; }
.rdot {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    border: 3px solid transparent;
    transition: transform 0.15s, border-color 0.15s;
}
.rdot:hover { transform: scale(1.12); }
.rdot.selected { border-color: #222; box-shadow: 0 0 0 3px rgba(0,0,0,0.15); }
.status-text { font-size: 0.85rem; color: #666; }
.status-ok { color: #28a745; font-weight: 600; }
.legend { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 10px; }
.legend .lg { display: flex; align-items: center; gap: 7px; font-size: 0.8rem; color: #555; }
.legend .lg i { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
.hm-hint {
    background: #fff7e6;
    border: 1px solid #ffe3a3;
    color: #7a5c00;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.84rem;
    margin-bottom: 14px;
}
.basemap-btn {
    border: 1.5px solid #d5ded9;
    background: #fff;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #444;
    cursor: pointer;
    transition: all 0.15s;
}
.basemap-btn:hover { border-color: #11998e; color: #11998e; }
.basemap-btn.active { background: #11998e; border-color: #11998e; color: #fff; }
.no-data-message {
    background: #fff7e6;
    border: 2px solid #ffe3a3;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    margin: 20px 0;
    display: none;
}
.no-data-message.show { display: block; }
</style>
@endpush

@section('content')

<div class="hm-hint">
    <i class="fas fa-lightbulb"></i>
    <b>Malilipot Municipality Hazard Map</b> - Shows all hazard zones for the entire municipality.
    <br><i class="fas fa-info-circle"></i> Some barangays may not have hazard map data yet. The map shows only barangays with saved hazard data.
</div>

<div class="hm-panel">
    <h3><i class="fas fa-map"></i> Malilipot Municipality Hazard Map</h3>
    <div class="hm-toolbar">
        <span class="hm-label">Tools:</span>
        <button class="hm-btn" data-tool="polygon"><i class="fas fa-draw-polygon"></i> Boundary</button>
        <button class="hm-btn" data-tool="hazard"><i class="fas fa-exclamation-triangle"></i> Hazard Zone</button>
        <button class="hm-btn" data-tool="point"><i class="fas fa-map-pin"></i> Point Marker</button>
        <button class="hm-btn" data-tool="select"><i class="fas fa-hand-pointer"></i> Select</button>
        <button class="hm-btn" data-tool="delete"><i class="fas fa-trash"></i> Delete</button>
        <div class="hm-facilities">
            <button class="hm-facility-btn police active" data-facility="police"><i class="fas fa-police"></i> Police</button>
            <button class="hm-facility-btn fire" data-facility="fire"><i class="fas fa-fire"></i> Fire</button>
            <button class="hm-facility-btn hall" data-facility="hall"><i class="fas fa-building"></i> Hall</button>
        </div>
        <div class="hm-risk-tools">
            <button class="hm-btn" data-risk="low"><i class="fas fa-smile"></i> Low</button>
            <button class="hm-btn" data-risk="medium"><i class="fas fa-meh"></i> Medium</button>
            <button class="hm-btn" data-risk="high"><i class="fas fa-frown"></i> High</button>
            <button class="hm-btn" data-risk="critical"><i class="fas fa-dizzy"></i> Critical</button>
        </div>
        <button class="hm-btn" data-tool="prone"><i class="fas fa-exclamation-circle"></i> Prone Area</button>
        <div class="hm-actions">
            <button class="hm-btn" id="undoBtn" disabled><i class="fas fa-undo"></i> Undo</button>
            <button class="hm-btn" id="cancelBtn" disabled><i class="fas fa-times"></i> Cancel</button>
            <button class="hm-save" id="saveBtn" disabled><i class="fas fa-save"></i> Save Map</button>
        </div>
    </div>
    <div class="hm-row">
        <div class="risk-dots" id="riskDots"></div>
        <span class="status-text" id="mapStatus">Loading hazard data...</span>
    </div>
    <div class="legend">
        @if (hasBoundary)
            <span class="lg"><i style="background:#0072C6;"></i> Municipality Boundary</span>
        @else
            <span class="lg" style="color:#777;">No boundary drawn yet</span>
        @endif
        <span class="lg"><i style="background:#5cb85c;"></i> Low Risk Zone</span>
        <span class="lg"><i style="background:#f0ad4e;"></i> Medium Risk Zone</span>
        <span class="lg"><i style="background:#d9534f;"></i> High Risk Zone</span>
        <span class="lg"><i style="background:#7b1a1a;"></i> Critical Risk Zone</span>
        <span class="lg"><i style="background:#9013fe;"></i> Point Marker</span>
    </div>
    <div style="margin-top:14px;">
        <div class="map-toggles" style="display:flex;gap:8px;margin-bottom:10px;">
            <button class="hm-btn basemap-btn active" data-basemap="streets"><i class="fas fa-map"></i> Streets</button>
            <button class="hm-btn basemap-btn" data-basemap="sat"><i class="fas fa-satellite"></i> Satellite</button>
        </div>
        <div id="map"></div>
        <div class="no-data-message" id="noDataMessage">
            <i class="fas fa-exclamation-triangle"></i>
            <p>No hazard map data saved yet. <a href="{{ route('malilipot.hazard_map') }}" style="color:#11998e;">Draw your first hazard zone</a></p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const MAPDATA = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
const CSRFTOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const API_BASE = '{{ url('admin/api/hazard-map') }}';

const RISK_COLORS = { Low: '#5cb85c', Medium: '#f0ad4e', High: '#d9534f', Critical: '#7b1a1a' };
const RISK_ORDER = ['Low', 'Medium', 'High', 'Critical'];
const ICONS = { Low: 'fa-smile', Medium: 'fa-meh', High: 'fa-frown', Critical: 'fa-dizzy' };

let curRisk = MAPDATA.risk_level || 'Low';
let curProneRisk = 'medium';
let layers = { base: null, features: L.layerGroup() };
let tempLayer = null;
let drawing = false;
let pendingPoints = null;
let proneDrawing = false;
let proneTempLayer = null;
let pendingPronePoints = null;
let toolState = { cur: 'select' };
let polyCount = 0;
let pointCount = 0;
let hasBoundary = MAPDATA.polygons.some(function (p) { return p.kind === 'boundary'; });
let undoStack = [];

let streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap'
});
let satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, attribution: '&copy; Esri'
});

let startLat = typeof MAPDATA.lat === 'number' ? MAPDATA.lat : 13.3597;
let startLng = typeof MAPDATA.lng === 'number' ? MAPDATA.lng : 123.7311;
let startZoom = MAPDATA.zoom || 7;

let map = L.map('map', {
    center: [startLat, startLng],
    zoom: startZoom,
    layers: [streets]
});

function markerIcon(color) {
    return L.divIcon({
        className: 'hm-marker',
        html: '<span style="display:block;width:22px;height:22px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.4);"></span>',
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });
}

function setBasemap(name) {
    if (name === 'sat') { map.removeLayer(streets); map.addLayer(satellite); }
    else { map.removeLayer(satellite); map.addLayer(streets); }
    document.querySelectorAll('.basemap-btn').forEach(function (b) {
        b.classList.toggle('active', b.dataset.basemap === name);
    });
}

function applyViewLock(isLocked) {
    if (isLocked) {
        map.dragging.disable();
        map.scrollWheelZoom.disable();
        map.doubleClickZoom.disable();
        map.touchZoom.disable();
        map.boxZoom.disable();
        map.keyboard.disable();
        if (map.tap) map.tap.disable();
    } else {
        map.dragging.enable();
        map.scrollWheelZoom.enable();
        map.doubleClickZoom.enable();
        map.touchZoom.enable();
        map.boxZoom.enable();
        map.keyboard.enable();
        if (map.tap) map.tap.enable();
    }
}

function updateStep() {
    document.querySelectorAll('.hm-btn[data-tool]').forEach(function (b) {
        b.disabled = !hasBoundary && b.dataset.tool !== 'polygon' && b.dataset.tool !== 'select';
    });
    document.getElementById('saveBtn').disabled = !hasBoundary;
    if (!hasBoundary && toolState.cur !== 'polygon' && toolState.cur !== 'select') {
        setTool('select');
    }
    syncToolButtons();
}

function syncToolButtons() {
    document.querySelectorAll('.hm-btn[data-tool]').forEach(function (b) {
        b.classList.toggle('active', b.dataset.tool === toolState.cur);
    });
}

function setTool(tool) {
    toolState.cur = tool;
    cancelDraw();
    cancelDrawProne();
    if (tool === 'delete' || tool === 'select') {
        layers.features.eachLayer(function (ly) {
            if (ly.feature) { ly.unbindPopup(); ly.off('click'); }
        });
    }
    attachLayerEvents();
    syncToolButtons();
}

function layerColor(kind, risk, ringRisk) {
    if (kind === 'boundary') return '#0072C6';
    if (kind === 'hazard') return RISK_COLORS[risk] || RISK_COLORS.Low;
    return RISK_COLORS[risk] || RISK_COLORS.Low;
}

function commitBoundary(points) {
    if (hasBoundary) { setStatus('Boundary already exists. Delete it first to redraw.', false); return; }
    let feat = {
        id: 'poly-' + (++polyCount),
        kind: 'boundary',
        name: 'Malilipot Municipality Boundary',
        ptype: 'polygon',
        color: '#0072C6',
        type: 'freehand',
        points: points
    };
    MAPDATA.polygons.push(feat);
    hasBoundary = true;
    drawFeature(feat);
    updateStep();
    setStatus('Boundary committed. You can now add hazard zones and markers.', true);
}

function commitHazard(points) {
    let risk = prompt('Enter risk level for this zone (Low, Medium, High, Critical):', curRisk || 'Low');
    if (!risk || RISK_ORDER.indexOf(risk) === -1) { setStatus('Hazard zone cancelled - invalid risk level.', false); return; }
    let name = prompt('Enter a name/label for this hazard zone:', 'Hazard Zone ' + (MAPDATA.polygons.filter(function (p) { return p.kind === 'hazard'; }).length + 1));
    let feat = {
        id: 'poly-' + (++polyCount),
        kind: 'hazard',
        name: name || 'Hazard Zone',
        ptype: 'polygon',
        color: RISK_COLORS[risk],
        risk: risk,
        type: 'freehand',
        points: points
    };
    MAPDATA.polygons.push(feat);
    drawFeature(feat);
    setStatus('Hazard zone added.', true);
}

function commitPoint(latlng) {
    let risk = prompt('Enter risk level for this marker (Low, Medium, High, Critical):', curRisk || 'Low');
    if (!risk || RISK_ORDER.indexOf(risk) === -1) { setStatus('Marker cancelled - invalid risk level.', false); return; }
    let name = prompt('Enter a name/label for this marker:', 'Point ' + (MAPDATA.points.length + 1));
    let feat = {
        id: 'pt-' + (++pointCount),
        kind: 'point',
        name: name || 'Point Marker',
        color: RISK_COLORS[risk],
        risk: risk,
        lat: latlng.lat,
        lng: latlng.lng
    };
    MAPDATA.points.push(feat);
    drawFeature(feat);
    setStatus('Point marker added.', true);
}

function selectedPoints() {
    return pendingPoints
        ? pendingPoints.map(function (pt) { return [pt.lat, pt.lng]; })
        : [];
}

function drawFeature(feat) {
    let isLine = feat.kind === 'prone' ? feat.type !== 'polygon' : (feat.type === 'line' || feat.ptype === 'line');
    if (isLine) {
        let line = L.polyline(feat.points || [], {
            color: feat.color || '#0072C6',
            weight: feat.kind === 'prone' ? 7 : 6,
            opacity: 0.95
        });
        line.feature = feat;
        line.bindPopup(popupHtml(feat));
        line.on('click', onFeatureClick);
        line.addTo(layers.features);
    } else if (feat.ptype === 'polygon' || Array.isArray(feat.points)) {
        let pts = feat.points || [];
        let poly = L.polygon(pts, {
            color: feat.color || '#0072C6',
            weight: 3,
            fillColor: feat.color || '#0072C6',
            fillOpacity: feat.kind === 'prone' ? 1 : 0.25
        });
        poly.feature = feat;
        poly.bindPopup(popupHtml(feat));
        poly.on('click', onFeatureClick);
        poly.addTo(layers.features);
    } else if (feat.ptype === 'circle') {
        let circle = L.circle([feat.center.lat, feat.center.lng], {
            radius: feat.radius || 300,
            color: feat.color || '#0072C6',
            weight: 2,
            fillColor: feat.color || '#0072C6',
            fillOpacity: 0.22
        });
        circle.feature = feat;
        circle.bindPopup(popupHtml(feat));
        circle.on('click', onFeatureClick);
        circle.addTo(layers.features);
    } else {
        let marker = L.marker([feat.lat, feat.lng], { icon: markerIcon(feat.color || RISK_COLORS.Low) });
        marker.feature = feat;
        marker.bindPopup(popupHtml(feat));
        marker.on('click', onFeatureClick);
        marker.addTo(layers.features);
    }
}

function popupHtml(feat) {
    let risk = feat.risk || (feat.kind === 'prone' ? ({ '#ffed4a': 'Low', '#fd7e14': 'Medium', '#dc3545': 'High' }[String(feat.color || '').toLowerCase()] || 'High') : '');
    let detail = feat.kind === 'prone' ? (feat.ptype || 'Other') + ' Prone' : (feat.kind === 'boundary' ? 'Municipality Boundary' : (feat.kind === 'hazard' ? 'Hazard Zone' : 'Point Marker'));
    return '<b>' + (feat.name || 'Feature') + '</b>' + (risk ? '<br>Risk: <b>' + risk + '</b>' : '') +
        '<br><small>' + detail + '</small>';
}

function attachLayerEvents() {
    layers.features.eachLayer(function (ly) {
        if (!ly.feature) return;
        if (toolState.cur === 'delete') {
            ly.unbindPopup();
            ly.off('click');
            ly.on('click', function () {
                MAPDATA.polygons = MAPDATA.polygons.filter(function (p) { return p.id !== ly.feature.id; });
                MAPDATA.points = MAPDATA.points.filter(function (p) { return p.id !== ly.feature.id; });
                if (ly.feature.kind === 'boundary') hasBoundary = false;
                layers.features.clearLayers();
                snapshotLayers();
                renderLayers();
                updateStep();
                setStatus('Feature deleted.', true);
            });
        } else if (toolState.cur === 'select') {
            ly.off('click');
            ly.on('click', onFeatureClick);
        }
    });
}

function onFeatureClick(e) {
    if (!e.target.feature) return;
    if (toolState.cur === 'delete' || toolState.cur === 'select') {
        e.target.openPopup();
    }
}

function cancelDraw() {
    if (tempLayer) {
        tempLayer.remove();
        tempLayer = null;
    }
    pendingPoints = null;
    drawing = false;
    document.getElementById('cancelBtn').disabled = true;
}

function snapshotLayers() {
    undoStack.push({
        polygons: JSON.parse(JSON.stringify(MAPDATA.polygons)),
        points: JSON.parse(JSON.stringify(MAPDATA.points)),
        hasBoundary: hasBoundary
    });
    if (undoStack.length > 20) undoStack.shift();
    updateUndoBtn();
}

function updateUndoBtn() {
    document.getElementById('undoBtn').disabled = undoStack.length === 0;
}

function doUndo() {
    let snap = undoStack.pop();
    if (!snap) return;
    MAPDATA.polygons = snap.polygons;
    MAPDATA.points = snap.points;
    hasBoundary = snap.hasBoundary;
    cancelDraw();
    layers.features.clearLayers();
    renderLayers();
    updateStep();
    updateUndoBtn();
    setStatus('Undo applied.', true);
}

function renderLayers() {
    layers.features.clearLayers();
    MAPDATA.polygons.forEach(drawFeature);
    MAPDATA.points.forEach(drawFeature);
    orderProneLayersByRisk();
    attachLayerEvents();
    applyViewLock(hasBoundary);
    updateStep();
}

function orderProneLayersByRisk() {
    const priority = { low: 1, medium: 2, high: 3, critical: 3 };
    const colorRisk = { '#ffed4a': 'low', '#fd7e14': 'medium', '#dc3545': 'high' };
    const proneLayers = [];
    layers.features.eachLayer(function (layer) {
        const feature = layer.feature;
        if (feature && feature.kind === 'prone') {
            const risk = String(colorRisk[String(feature.color || '').toLowerCase()] || feature.risk || 'high').toLowerCase();
            proneLayers.push({ layer: layer, priority: priority[risk] || 3 });
        }
    });
    proneLayers
        .sort(function (a, b) { return a.priority - b.priority; })
        .forEach(function (item) { item.layer.bringToFront(); });
}

function setStatus(msg, ok) {
    let el = document.getElementById('mapStatus');
    el.className = 'status-text' + (ok ? ' status-ok' : '');
    el.textContent = msg;
}

function renderRiskDots() {
    let wrap = document.getElementById('riskDots');
    wrap.innerHTML = RISK_ORDER.map(function (r) {
        return '<div class="rdot ' + (r === curRisk ? 'selected' : '') + '" data-risk="' + r + '" title="' + r + '" style="background:' + RISK_COLORS[r] + ';">' +
            '<i class="fas ' + ICONS[r] + '"></i></div>';
    }).join('');
    document.querySelectorAll('.rdot').forEach(function (dot) {
        dot.onclick = function () { setRiskLevel(dot.dataset.risk); };
    });
}

function setRiskLevel(level) {
    level = RISK_ORDER.find(function (risk) { return risk.toLowerCase() === String(level).toLowerCase(); }) || level;
    curRisk = level;
    curProneRisk = String(level).toLowerCase();
    renderRiskDots();
    let fd = new FormData();
    fd.append('action', 'set_risk');
    fd.append('risk_level', level);
    fetch(API_BASE + '/set_risk', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRFTOKEN } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) setStatus('Risk level set to ' + level + '.', true);
            else setStatus('Failed to update risk level.', false);
        })
        .catch(function () { setStatus('Failed to update risk level.', false); });
}

function buildPayload() {
    let polygons = MAPDATA.polygons.map(function (p) {
        return {
            id: p.id, kind: p.kind, name: p.name, ptype: p.ptype,
            color: p.color, risk: p.risk || null, type: p.type || 'freehand',
            points: p.points || []
        };
    });
    let points = MAPDATA.points.map(function (pt) {
        return { id: pt.id, kind: pt.kind, name: pt.name, color: pt.color, risk: pt.risk || null, lat: pt.lat, lng: pt.lng };
    });
    let facilities = [];
    let proneAreas = [];
    // Collect facilities from MAPDATA
    MAPDATA.facilities || [];
    // Collect prone areas from MAPDATA
    MAPDATA.proneAreas || [];
    return { polygons: polygons, points: points, facilities: facilities, proneAreas: proneAreas };
}

function saveMap() {
    if (!hasBoundary) { setStatus('Draw and commit the boundary first.', false); return; }
    let payload = buildPayload();
    let fd = new FormData();
    fd.append('action', 'save');
    fd.append('map_lat', map.getCenter().lat);
    fd.append('map_lng', map.getCenter().lng);
    fd.append('map_zoom', map.getZoom());
    fd.append('hazard_polygons', JSON.stringify(payload.polygons));
    fd.append('hazard_points', JSON.stringify(payload.points));
    fd.append('facilities', JSON.stringify(payload.facilities));
    fd.append('proneAreas', JSON.stringify(payload.proneAreas));

    document.getElementById('saveBtn').disabled = true;
    let orig = document.getElementById('saveBtn').innerHTML;
    document.getElementById('saveBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch(API_BASE + '/save', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRFTOKEN } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('saveBtn').innerHTML = orig;
            document.getElementById('saveBtn').disabled = false;
            if (data.ok) {
                MAPDATA.updated_at = data.updated_at;
                setStatus('Hazard map saved successfully. (' + data.updated_at + ')', true);
            } else {
                setStatus('Failed to save hazard map.', false);
            }
        })
        .catch(function () {
            document.getElementById('saveBtn').innerHTML = orig;
            document.getElementById('saveBtn').disabled = false;
            setStatus('Failed to save hazard map.', false);
        });
}

function init() {
    layers.features.addTo(map);
    map.on('mousemove', function () { attachLayerEvents(); });
    document.querySelectorAll('.hm-btn[data-tool]').forEach(function (b) {
        b.onclick = function () { setTool(b.dataset.tool); };
    });
    document.getElementById('undoBtn').onclick = doUndo;
    document.getElementById('cancelBtn').onclick = function () { cancelDraw(); cancelDrawProne(); };
    document.getElementById('saveBtn').onclick = saveMap;
    document.querySelectorAll('.basemap-btn').forEach(function (b) {
        b.onclick = function () { setBasemap(b.dataset.basemap); };
    });
    document.querySelectorAll('.hm-facility-btn').forEach(function (b) {
        b.onclick = function () { setFacility(b.dataset.facility); };
    });
    document.querySelectorAll('.hm-risk-tools .hm-btn').forEach(function (b) {
        b.onclick = function () { setRiskLevel(b.dataset.risk); };
    });

    // Check if we have data
    if (MAPDATA.total_polygons === 0 && MAPDATA.total_points === 0) {
        document.getElementById('noDataMessage').classList.add('show');
        document.getElementById('map').style.display = 'none';
    } else {
        document.getElementById('noDataMessage').classList.remove('show');
        document.getElementById('map').style.display = 'block';
    }

    // If boundary exists, fit map to it
    if (hasBoundary) {
        // Find boundary polygon and fit map to its bounds
        let boundaryLayer = null;
        layers.features.eachLayer(function (ly) {
            if (ly.feature && ly.feature.kind === 'boundary') {
                boundaryLayer = ly;
            }
        });
        if (boundaryLayer) {
            map.fitBounds(boundaryLayer.getBounds(), { maxZoom: 10 });
        }
    }

    renderLayers();
    renderRiskDots();
    applyViewLock(hasBoundary);
    updateStep();
    setStatus(MAPDATA.updated_at === 'Not saved yet'
        ? 'Draw your boundary to begin.'
        : 'Last saved: ' + MAPDATA.updated_at, true);

    // Prone area drawing
    const PRONE_COLORS = { low: '#f1c40f', medium: '#e67e22', high: '#e74c3c' };
    const PRONE_RISKS = ['low', 'medium', 'high', 'critical'];

    map.on('click', function (e) {
        if (toolState.cur === 'point' && hasBoundary) commitPoint(e.latlng);
        if (toolState.cur === 'polygon' || (toolState.cur === 'hazard' && hasBoundary)) {
            if (!drawing) {
                drawing = true;
                pendingPoints = [{ lat: e.latlng.lat, lng: e.latlng.lng }];
                tempLayer = L.polygon(selectedPoints(), {
                    color: toolState.cur === 'polygon' ? '#0072C6' : (RISK_COLORS[curRisk] || '#5cb85c'),
                    weight: 3,
                    fillOpacity: 0.18
                }).addTo(map);
                document.getElementById('cancelBtn').disabled = false;
            } else if (pendingPoints && pendingPoints.length >= 3) {
                let pts = selectedPoints();
                cancelDraw();
                if (toolState.cur === 'polygon') commitBoundary(pts);
                else commitHazard(pts);
            }
        }
        if (toolState.cur === 'prone' && hasBoundary) {
            if (!proneDrawing) {
                proneDrawing = true;
                pendingPronePoints = [{ lat: e.latlng.lat, lng: e.latlng.lng }];
                proneTempLayer = L.polygon(selectedPronePoints(), {
                    color: PRONE_COLORS[curProneRisk] || '#e74c3c',
                    fillColor: PRONE_COLORS[curProneRisk] || '#e74c3c',
                    weight: 3,
                    fillOpacity: 1
                }).addTo(map);
                document.getElementById('cancelBtn').disabled = false;
            } else if (pendingPronePoints && pendingPronePoints.length >= 3) {
                let pts = selectedPronePoints();
                cancelDrawProne();
                commitProneArea(pts);
            }
        }
    });
    map.on('mousemove', function (e) {
        if (!drawing || !tempLayer || !pendingPoints) return;
        pendingPoints.push({ lat: e.latlng.lat, lng: e.latlng.lng });
        tempLayer.setLatLngs(selectedPoints());
    });
    map.on('mousemove', function (e) {
        if (!proneDrawing || !proneTempLayer || !pendingPronePoints) return;
        pendingPronePoints.push({ lat: e.latlng.lat, lng: e.latlng.lng });
        proneTempLayer.setLatLngs(selectedPronePoints());
    });
}

function selectedPronePoints() {
    return pendingPronePoints
        ? pendingPronePoints.map(function (pt) { return [pt.lat, pt.lng]; })
        : [];
}

let curFacility = 'police';
let facilityMarkers = {};

function setFacility(facility) {
    curFacility = facility;
    document.querySelectorAll('.hm-facility-btn').forEach(function (b) {
        b.classList.remove('active');
        if (b.dataset.facility === facility) b.classList.add('active');
    });
}

function commitFacility(latlng) {
    let name = prompt('Enter a name for this facility:', curFacility + ' Station');
    if (!name) { setStatus('Facility cancelled.', false); return; }
    let color = '#';
    if (facility === 'police') color = '#C0392B';
    else if (facility === 'fire') color = '#E67E22';
    else if (facility === 'hall') color = '#2C3E50';
    
    let feat = {
        id: 'fac-' + Date.now(),
        kind: 'facility',
        name: name,
        type: 'marker',
        color: color,
        lat: latlng.lat,
        lng: latlng.lng
    };
    MAPDATA.facilities = MAPDATA.facilities || [];
    MAPDATA.facilities.push(feat);
    drawFacility(feat);
    setStatus('Facility added.', true);
    updateFacilityCount();
}

function drawFacility(feat) {
    let iconHtml = '<div style="width:25px;height:25px;border-radius:50%;background:' + feat.color + ';border:3px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;font-weight:bold;color:#fff;">' + (feat.name.substring(0,2).toUpperCase()) + '</div>';
    let marker = L.marker([feat.lat, feat.lng], { icon: L.divIcon({ html: iconHtml, iconSize: [25, 25], iconAnchor: [12, 12] }) });
    marker.feature = feat;
    marker.bindPopup('<b>' + feat.name + '</b><br>' + feat.kind);
    marker.addTo(layers.features);
    facilityMarkers[feat.id] = marker;
}

function updateFacilityCount() {
    let count = (MAPDATA.facilities || []).length;
    setStatus('Facilities: ' + count, true);
}

function cancelDrawProne() {
    if (proneTempLayer) {
        proneTempLayer.remove();
        proneTempLayer = null;
    }
    pendingPronePoints = null;
    proneDrawing = false;
    document.getElementById('cancelBtn').disabled = true;
}

function commitProneArea(points) {
    let defaultRisk = curProneRisk === 'critical' ? 'high' : curProneRisk;
    let risk = prompt('Enter risk level (low, medium, high):', defaultRisk || 'medium');
    if (!risk || !['low', 'medium', 'high'].includes(risk.toLowerCase())) { setStatus('Prone area cancelled - invalid risk level.', false); return; }
    risk = risk.toLowerCase();
    let proneType = prompt('Enter prone area type (Flood, Storm, Landslide, Volcanic, Earthquake, Fire, Erosion, Other):', 'Flood');
    if (!proneType) { setStatus('Prone area cancelled.', false); return; }
    let name = prompt('Enter a name for this prone area:', proneType);
    if (name === null) { setStatus('Prone area cancelled.', false); return; }
    let color = { low: '#ffed4a', medium: '#fd7e14', high: '#dc3545' }[risk];
    let feat = {
        id: 'prone-' + Date.now(),
        kind: 'prone',
        name: name || 'Prone Area',
        ptype: proneType,
        type: 'polygon',
        color: color,
        risk: risk.charAt(0).toUpperCase() + risk.slice(1),
        points: points
    };
    MAPDATA.polygons.push(feat);
    drawFeature(feat);
    orderProneLayersByRisk();
    setStatus('Prone area added - Risk: ' + risk + '.', true);
    updateProneCount();
    cancelDrawProne();
}

function updateProneCount() {
    let count = MAPDATA.polygons.filter(function (feature) { return feature.kind === 'prone'; }).length;
    let el = document.getElementById('proneCount');
    if (el) el.textContent = count;
}
</script>
@endpush

<div class="toast" id="toast" style="position: fixed; bottom: 20px; right: 20px; padding: 16px 24px; border-radius: var(--radius); color: white; font-weight: 500; z-index: 3000; display: none;">
    <span id="toast-message">Saving...</span>
</div>
</div>