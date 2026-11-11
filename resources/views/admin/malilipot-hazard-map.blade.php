@extends('layouts.admin')

@section('title', 'Malilipot Municipality Hazard Map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.municipal-map { display: grid; grid-template-columns: minmax(240px, 320px) minmax(0, 1fr); gap: 18px; }
.map-summary, .map-canvas { background: #fff; border: 1px solid #BFDBFE; border-radius: 12px; }
.map-summary { padding: 20px; }
.map-summary h2 { color: #1E3A8A; font-size: 1.05rem; margin: 0 0 14px; }
.map-summary p { color: #475569; font-size: .88rem; line-height: 1.5; margin: 0 0 14px; }
.map-stat { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-top: 1px solid #E9EFF5; color: #475569; font-size: .88rem; }
.map-stat strong { color: #1E3A8A; }
.risk-badge { display: inline-block; border-radius: 14px; background: #1E40AF; color: #fff; padding: 4px 11px; font-size: .78rem; }
.map-canvas { min-height: 500px; overflow: hidden; }
#municipal-hazard-map { width: 100%; height: 100%; min-height: 500px; }
.map-empty { border-radius: 10px; background: #EFF6FF; color: #475569; padding: 12px 14px; font-size: .88rem; }
@media (max-width: 900px) { .municipal-map { grid-template-columns: 1fr; } .map-canvas, #municipal-hazard-map { min-height: 420px; } }
</style>
@endpush

@section('content')
<div class="municipal-map">
    <section class="map-summary" aria-label="Hazard map summary">
        <h2>Malilipot Municipality, Albay</h2>
        <p>Municipality-wide hazard map. This view is read-only.</p>
        <div class="map-stat"><span>Overall risk</span><strong><span class="risk-badge">{{ $mapData['risk_level'] }}</span></strong></div>
        <div class="map-stat"><span>Hazard zones</span><strong>{{ number_format($mapData['total_polygons']) }}</strong></div>
        <div class="map-stat"><span>Point markers</span><strong>{{ number_format($mapData['total_points']) }}</strong></div>
        <div class="map-stat"><span>Last saved</span><strong>{{ $mapData['updated_at'] ?? 'Not saved yet' }}</strong></div>
        @if ($mapData['total_polygons'] === 0 && $mapData['total_points'] === 0)
            <div class="map-empty" role="status">No municipality hazard-map data has been saved yet.</div>
        @endif
    </section>
    <div class="map-canvas">
        <div id="municipal-hazard-map" role="region" aria-label="Malilipot municipality hazard map"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const MAPDATA = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
const map = L.map('municipal-hazard-map', {
    center: [MAPDATA.lat, MAPDATA.lng],
    zoom: MAPDATA.zoom,
    scrollWheelZoom: false
});
map.createPane('boundaryMaskPane');
map.getPane('boundaryMaskPane').style.zIndex = 650;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

function popup(title, detail) {
    const container = document.createElement('div');
    const heading = document.createElement('strong');
    heading.textContent = title;
    container.appendChild(heading);
    if (detail) {
        const description = document.createElement('div');
        description.textContent = detail;
        container.appendChild(description);
    }
    return container;
}

const features = L.featureGroup();
const proneLayers = [];
(MAPDATA.polygons || []).forEach(function (polygon) {
    const color = polygon.color || '#0072C6';
    if (polygon.ptype === 'circle' && polygon.center) {
        L.circle([polygon.center.lat, polygon.center.lng], {
            radius: polygon.radius || 300,
            color: color,
            fillColor: color,
            fillOpacity: 0.22
        }).addTo(features);
    } else if (Array.isArray(polygon.points) && polygon.points.length) {
        const isLine = polygon.kind === 'prone' ? polygon.type !== 'polygon' : polygon.type === 'line';
        const layer = isLine
            ? L.polyline(polygon.points, { color: color, weight: 7, opacity: 0.95, lineCap: 'round', lineJoin: 'round' })
            : L.polygon(polygon.points, { color: color, fillColor: color, fillOpacity: polygon.kind === 'prone' ? 1 : 0.25 });
        const proneRisk = { '#ffed4a': 'Low', '#fd7e14': 'Medium', '#dc3545': 'High' }[String(polygon.color || '').toLowerCase()];
        const detail = polygon.kind === 'prone'
            ? (polygon.ptype || 'Other') + ' Prone - ' + (polygon.risk || proneRisk || 'High') + ' risk'
            : (polygon.risk || '');
        layer.bindPopup(popup(polygon.name || 'Hazard zone', detail));
        layer.addTo(features);
        if (polygon.kind === 'prone') proneLayers.push({ layer: layer, color: polygon.color, risk: polygon.risk });
    }
});

(MAPDATA.points || []).forEach(function (point) {
    if (Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lng))) {
        L.circleMarker([Number(point.lat), Number(point.lng)], {
            radius: 7,
            color: '#fff',
            weight: 2,
            fillColor: point.color || '#9013fe',
            fillOpacity: 1
        }).addTo(features);
    }
});

features.addTo(map);
orderProneLayersByRisk();
const boundary = MAPDATA.polygons.find(function (polygon) {
    return polygon.kind === 'boundary' && Array.isArray(polygon.points) && polygon.points.length;
});
if (boundary) {
    const worldRing = [[-85, -180], [-85, 180], [85, 180], [85, -180]];
    L.polygon([worldRing, boundary.points], {
        pane: 'boundaryMaskPane',
        stroke: false,
        fillColor: '#ffffff',
        fillOpacity: 1,
        fillRule: 'evenodd',
        interactive: false
    }).addTo(map);
    map.fitBounds(L.polygon(boundary.points).getBounds(), { padding: [40, 40] });
} else if (features.getLayers().length) {
    map.fitBounds(features.getBounds().pad(0.15), { maxZoom: 16 });
}

function orderProneLayersByRisk() {
    const priority = { low: 1, medium: 2, high: 3, critical: 3 };
    const colorRisk = { '#ffed4a': 'low', '#fd7e14': 'medium', '#dc3545': 'high' };
    proneLayers
        .slice()
        .sort(function (a, b) {
            const riskA = String(colorRisk[String(a.color || '').toLowerCase()] || a.risk || 'high').toLowerCase();
            const riskB = String(colorRisk[String(b.color || '').toLowerCase()] || b.risk || 'high').toLowerCase();
            return (priority[riskA] || 3) - (priority[riskB] || 3);
        })
        .forEach(function (item) {
            if (map.hasLayer(item.layer)) item.layer.bringToFront();
        });
}
</script>
@endpush
