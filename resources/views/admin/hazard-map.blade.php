@extends('layouts.admin')

@section('title', 'Admin - Malilipot Hazard Map')
@section('headerTitle', 'Admin - Malilipot Hazard Map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .municipal-map { display: grid; grid-template-columns: 250px 1fr; gap: 18px; padding: 20px; }
    .map-summary, .map-canvas { background: #fff; border: 1px solid #BFDBFE; border-radius: 12px; }
    .map-summary { padding: 20px; }
    .map-summary h2 { color: #1E3A8A; font-size: 1.05rem; margin: 0 0 14px; }
    .map-stat { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-top: 1px solid #E9EFF5; color: #475569; font-size: .88rem; }
    .map-stat strong { color: #1E3A8A; text-align: right; }
    .risk-badge { display: inline-block; border-radius: 14px; background: #1E40AF; color: #fff; padding: 4px 11px; font-size: .78rem; }
    .map-canvas { min-height: 500px; overflow: hidden; }
    #municipal-hazard-map { width: 100%; height: 100%; min-height: 500px; }
    .map-empty { border-radius: 10px; background: #EFF6FF; color: #475569; padding: 12px 14px; font-size: .88rem; margin-top: 14px; }
    @media (max-width: 900px) { .municipal-map { grid-template-columns: 1fr; } .map-canvas, #municipal-hazard-map { min-height: 420px; } }
</style>
@endpush

@section('content')
<div class="municipal-map">
    <section class="map-summary" aria-label="Hazard map summary">
        <h2>Malilipot Municipality, Albay</h2>
        <p>Use the tools below to draw the municipality boundary. <strong>Current boundary data will be replaced.</strong></p>
        <div class="map-stat"><span>Overall risk</span><strong><span class="risk-badge">{{ $mapData['risk_level'] }}</span></strong></div>
        <div class="map-stat"><span>Hazard zones</span><strong>{{ $mapData['total_polygons'] }}</strong></div>
        <div class="map-stat"><span>Point markers</span><strong>{{ $mapData['total_points'] }}</strong></div>
        <div class="map-stat"><span>Last saved</span><strong>{{ $mapData['updated_at'] ?: 'Not saved yet' }}</strong></div>
    </section>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const mapData = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
const CSRFTOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const API_BASE = '{{ url('malilipot/api/hazard-map') }}';

let layers = { base: null, features: L.layerGroup() };
let tempLayer = null;
let drawing = false;
let pendingPoints = null;
let toolState = { cur: 'select' };
let polyCount = 0;
let pointCount = 0;
let hasBoundary = false;
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

let map = L.map('municipal-hazard-map', {
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
    if (feat.ptype === 'polygon' || Array.isArray(feat.points)) {
        let pts = feat.points || [];
        let poly = L.polygon(pts, {
            color: feat.color || '#0072C6',
            weight: 3,
            fillColor: feat.color || '#0072C6',
            fillOpacity: 0.25
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
    let risk = feat.risk ? '<br>Risk: <b>' + feat.risk + '</b>' : '';
    return '<b>' + (feat.name || 'Feature') + '</b>' + risk +
        '<br><small>' + (feat.kind === 'boundary' ? 'Municipality Boundary' : (feat.kind === 'hazard' ? 'Hazard Zone' : 'Point Marker')) + '</small>';
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
    attachLayerEvents();
    applyViewLock(hasBoundary);
    updateStep();
    setStatus(MAPDATA.updated_at === 'Not saved yet'
        ? 'Draw your boundary to begin.'
        : 'Last saved: ' + MAPDATA.updated_at, true);
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
    curRisk = level;
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
    return { polygons: polygons, points: points };
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
    document.getElementById('cancelBtn').onclick = function () { cancelDraw(); };
    document.getElementById('saveBtn').onclick = saveMap;
    document.querySelectorAll('.basemap-btn').forEach(function (b) {
        b.onclick = function () { setBasemap(b.dataset.basemap); };
    });

    // Check if we have data
    if (MAPDATA.total_polygons === 0 && MAPDATA.total_points === 0) {
        document.getElementById('noDataMessage').classList.add('show');
        document.getElementById('map').style.display = 'none';
    } else {
        document.getElementById('noDataMessage').classList.remove('show');
        document.getElementById('map').style.display = 'block';
    }

    renderLayers();
    renderRiskDots();
    applyViewLock(hasBoundary);
    updateStep();
    setStatus(MAPDATA.updated_at === 'Not saved yet'
        ? 'Draw your boundary to begin.'
        : 'Last saved: ' + MAPDATA.updated_at, true);

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
    });
    map.on('mousemove', function (e) {
        if (!drawing || !tempLayer || !pendingPoints) return;
        pendingPoints.push({ lat: e.latlng.lat, lng: e.latlng.lng });
        tempLayer.setLatLngs(selectedPoints());
    });
}

init();

</script>
@endpush

<div class="toast" id="toast" style="position: fixed; bottom: 20px; right: 20px; padding: 16px 24px; border-radius: var(--radius); color: white; font-weight: 500; z-index: 3000; display: none;">
    <span id="toast-message">Saving...</span>
</div>