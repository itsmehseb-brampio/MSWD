@extends('layouts.admin')

@section('title', 'Admin - Malilipot Hazard Map')
@section('headerTitle', 'Admin - Malilipot Hazard Map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .municipal-map { display: grid; grid-template-columns: minmax(240px, 320px) minmax(0, 1fr); gap: 18px; padding: 20px; }
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
        <div class="map-stat"><span>Overall risk</span><strong><span class="risk-badge">{{ $mapData['risk_level'] }}</span></strong></div>
        <div class="map-stat"><span>Hazard zones</span><strong>{{ $mapData['total_polygons'] }}</strong></div>
        <div class="map-stat"><span>Point markers</span><strong>{{ $mapData['total_points'] }}</strong></div>
        <div class="map-stat"><span>Last saved</span><strong>{{ $mapData['updated_at'] ?: 'Not saved yet' }}</strong></div>
        @unless($mapData['has_data'])
            <div class="map-empty" role="status">No municipality hazard-map data has been saved yet.</div>
        @endunless
    </section>
    <div class="map-canvas">
        <div id="municipal-hazard-map" role="region" aria-label="Malilipot municipality hazard map"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const mapData = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
const map = L.map('municipal-hazard-map', {
    center: [mapData.lat, mapData.lng],
    zoom: mapData.zoom,
    scrollWheelZoom: false
});

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

function safeColor(value, fallback) {
    return typeof value === 'string' && /^#[0-9a-f]{3,8}$/i.test(value) ? value : fallback;
}

const features = L.featureGroup();
(mapData.polygons || []).forEach(function (polygon) {
    const color = safeColor(polygon.color, '#0072C6');
    let layer = null;
    if (polygon.ptype === 'circle' && polygon.center &&
        Number.isFinite(Number(polygon.center.lat)) &&
        Number.isFinite(Number(polygon.center.lng))) {
        layer = L.circle([Number(polygon.center.lat), Number(polygon.center.lng)], {
            radius: Number(polygon.radius) || 300,
            color: color,
            fillColor: color,
            fillOpacity: 0.22
        });
    } else if (Array.isArray(polygon.points) && polygon.points.length) {
        layer = polygon.type === 'line'
            ? L.polyline(polygon.points, { color: color, weight: 5 })
            : L.polygon(polygon.points, { color: color, fillColor: color, fillOpacity: 0.25 });
    }
    if (layer) {
        layer.bindPopup(popup(polygon.name || 'Hazard zone', polygon.risk || polygon.barangay_name || ''));
        layer.addTo(features);
    }
});

(mapData.points || []).forEach(function (point) {
    if (!Number.isFinite(Number(point.lat)) || !Number.isFinite(Number(point.lng))) {
        return;
    }
    L.circleMarker([Number(point.lat), Number(point.lng)], {
        radius: 7,
        color: '#fff',
        weight: 2,
        fillColor: safeColor(point.color, '#9013fe'),
        fillOpacity: 1
    }).bindPopup(popup(point.name || 'Hazard point', point.risk || point.barangay_name || '')).addTo(features);
});

features.addTo(map);
if (features.getLayers().length) {
    map.fitBounds(features.getBounds().pad(0.15), { maxZoom: 16 });
}
</script>
@endpush
