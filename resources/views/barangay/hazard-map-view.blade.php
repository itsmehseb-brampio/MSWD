@extends('layouts.barangay')

@section('title', 'View Hazard Map')
@section('headerTitle', 'View Hazard Map')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.hm-row { display: grid; grid-template-columns: 330px 1fr; gap: 18px; align-items: start; }
@media (max-width: 900px) { .hm-row { grid-template-columns: 1fr; } }
.hm-info {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.hm-info h3 { font-size: 1rem; color: #333; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.hm-info h3 i { color: #11998e; }
.info-line { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f1f4f2; font-size: 0.88rem; }
.info-line:last-child { border-bottom: none; }
.info-line .il-label { color: #777; }
.info-line .il-value { font-weight: 700; color: #222; }
.risk-badge-x {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 20px;
    color: #fff;
    font-weight: 700;
    font-size: 0.8rem;
}
.legend { margin-top: 16px; }
.legend .lg { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #555; padding: 5px 0; }
.legend .lg i { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
#vmap { width: 100%; height: 560px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.hm-empty {
    background: #fff7e6;
    border: 1px solid #ffe3a3;
    color: #7a5c00;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.85rem;
    margin-bottom: 14px;
}
</style>
@endsection

@section('content')

@php
    $riskColors = ['Low' => '#28a745', 'Medium' => '#fd7e14', 'High' => '#dc3545', 'Critical' => '#7b1a1a'];
    $risk = $mapData['risk_level'] ?? 'Low';
    $boundaryCount = count(array_filter($mapData['polygons'], fn ($p) => ($p['kind'] ?? '') === 'boundary'));
    $hazardCount = count(array_filter($mapData['polygons'], fn ($p) => ($p['kind'] ?? '') !== 'boundary'));
    $pointCount = count($mapData['points']);
@endphp

@if ($boundaryCount === 0)
    <div class="hm-empty">
        <i class="fas fa-exclamation-circle"></i>
        No hazard map saved yet. <a href="{{ route('barangay.hazard_map') }}" style="color:#11998e;font-weight:700;">Draw your hazard map</a>
    </div>
@endif

<div class="hm-row">
    <div>
        <div class="hm-info">
            <h3><i class="fas fa-info-circle"></i> Hazard Map Summary</h3>
            <div class="info-line">
                <span class="il-label">Risk Level</span>
                <span class="il-value"><span class="risk-badge-x" style="background:{{ $riskColors[$risk] ?? '#28a745' }};">{{ $risk }}</span></span>
            </div>
            <div class="info-line">
                <span class="il-label">Last Updated</span>
                <span class="il-value">{{ $mapData['updated_at'] }}</span>
            </div>
            <div class="info-line">
                <span class="il-label">Boundaries</span>
                <span class="il-value">{{ number_format($boundaryCount) }}</span>
            </div>
            <div class="info-line">
                <span class="il-label">Hazard Zones</span>
                <span class="il-value">{{ number_format($hazardCount) }}</span>
            </div>
            <div class="info-line">
                <span class="il-label">Point Markers</span>
                <span class="il-value">{{ number_format($pointCount) }}</span>
            </div>

            <div class="legend">
                <div class="lg"><i style="background:#0072C6;"></i> Barangay Boundary</div>
                <div class="lg"><i style="background:#5cb85c;"></i> Low Risk Zone</div>
                <div class="lg"><i style="background:#f0ad4e;"></i> Medium Risk Zone</div>
                <div class="lg"><i style="background:#d9534f;"></i> High Risk Zone</div>
                <div class="lg"><i style="background:#7b1a1a;"></i> Critical Risk Zone</div>
                <div class="lg"><i style="background:#9013fe;"></i> Point Marker</div>
            </div>
        </div>
    </div>

    <div>
        <div id="vmap"></div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const MAPDATA = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

let centerLat = typeof MAPDATA.lat === 'number' ? MAPDATA.lat : 13.3170;
let centerLng = typeof MAPDATA.lng === 'number' ? MAPDATA.lng : 123.7400;
let map = L.map('vmap', {
    center: [centerLat, centerLng],
    zoom: MAPDATA.zoom || 14,
    scrollWheelZoom: false
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

function markerIcon(color) {
    return L.divIcon({
        className: 'vm-marker',
        html: '<span style="display:block;width:22px;height:22px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.4);"></span>',
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });
}

let group = L.featureGroup();
(MAPDATA.polygons || []).forEach(function (p) {
    if (p.ptype === 'circle' && p.center) {
        L.circle([p.center.lat, p.center.lng], {
            radius: p.radius || 300,
            color: p.color || '#0072C6',
            weight: 2,
            fillColor: p.color || '#0072C6',
            fillOpacity: 0.22
        }).addTo(group);
    } else if (p.points && p.points.length) {
        L.polygon(p.points, {
            color: p.color || '#0072C6',
            weight: 3,
            fillColor: p.color || '#0072C6',
            fillOpacity: 0.25
        }).addTo(group);
    }
});
(MAPDATA.points || []).forEach(function (pt) {
    L.marker([pt.lat, pt.lng], { icon: markerIcon(pt.color || '#9013fe') })
        .addTo(group)
        .bindPopup('<b>' + (pt.name || 'Point Marker') + '</b><br><small>' + (pt.risk || '') + '</small>');
});

group.addTo(map);
if (group.getLayers().length) {
    map.fitBounds(group.getBounds().pad(0.15), { maxZoom: 16 });
}
</script>
@endpush