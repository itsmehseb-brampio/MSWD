@extends('layouts.barangay')

@section('title', 'Malilipot Hazard Map View')
@section('headerTitle', 'Malilipot Municipality Hazard Map View')

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
.municipality-badge {
    background: linear-gradient(135deg, #1e3a5f, #2d5a27);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 16px;
    margin-top: -8px;
}
.risk-bar {
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    margin-top: 8px;
}
.risk-bar-fill {
    height: 100%;
    width: 0%;
    transition: width 0.6s ease;
    border-radius: 5px;
}
.risk-bar-fill.low { background: #5cb85c; }
.risk-bar-fill.medium { background: #f0ad4e; }
.risk-bar-fill.high { background: #d9534f; }
.risk-bar-fill.critical { background: #7b1a1a; }
</style>
@endsection

@section('content')

@php
    $riskColors = ['Low' => '#5cb85c', 'Medium' => '#f0ad4e', 'High' => '#d9534f', 'Critical' => '#7b1a1a'];
    $risk = $mapData['risk_level'] ?? 'Low';
    $hasData = $mapData['has_data'] ?? false;
    $totalPolygons = $mapData['total_polygons'] ?? 0;
    $totalPoints = $mapData['total_points'] ?? 0;
@endphp

<div class="municipality-badge">Malilipot Municipality - Albay</div>

@if (!$hasData && $totalPolygons === 0 && $totalPoints === 0)
    <div class="hm-empty">
        <i class="fas fa-exclamation-circle"></i>
        No hazard map data saved yet for Malilipot municipality. <a href="{{ route('malilipot.hazard_map') }}" style="color:#11998e;font-weight:700;">Draw your hazard map</a>
    </div>
@endif

<div class="hm-row">
    <div>
        <div class="hm-info">
            <h3><i class="fas fa-info-circle"></i> Malilipot Municipality Hazard Map Summary</h3>

            @if ($hasData)
                <div class="info-line">
                    <span class="il-label">Risk Level</span>
                    <span class="il-value"><span class="risk-badge-x" style="background:{{ $riskColors[$risk] ?? '#5cb85c' }};">{{ $risk }}</span></span>
                </div>
                <div class="info-line">
                    <span class="il-label">Municipality Map</span>
                    <span class="il-value">Saved</span>
                </div>
                <div class="info-line">
                    <span class="il-label">Hazard Zones</span>
                    <span class="il-value">{{ number_format($totalPolygons) }}</span>
                </div>
                <div class="info-line">
                    <span class="il-label">Point Markers</span>
                    <span class="il-value">{{ number_format($totalPoints) }}</span>
                </div>
                <div class="risk-bar">
                    <div class="risk-bar-fill {{ strtolower($risk) }}" style="width:{{ calculateRiskPercentage($risk) }}%;"></div>
                </div>
            @else
                <div class="info-line">
                    <span class="il-label">Risk Level</span>
                    <span class="il-value">No data yet</span>
                </div>
                <div class="info-line">
                    <span class="il-label">Municipality Map</span>
                    <span class="il-value">Not saved</span>
                </div>
                <div class="info-line">
                    <span class="il-label">Hazard Zones</span>
                    <span class="il-value">0</span>
                </div>
                <div class="info-line">
                    <span class="il-label">Point Markers</span>
                    <span class="il-value">0</span>
                </div>
            @endif

            <div class="legend">
                @if ($hasData)
                    <div class="lg"><i style="background:#0072C6;"></i> Municipality Boundary</div>
                    <div class="lg"><i style="background:#5cb85c;"></i> Low Risk Zone</div>
                    <div class="lg"><i style="background:#f0ad4e;"></i> Medium Risk Zone</div>
                    <div class="lg"><i style="background:#d9534f;"></i> High Risk Zone</div>
                    <div class="lg"><i style="background:#7b1a1a;"></i> Critical Risk Zone</div>
                    <div class="lg"><i style="background:#9013fe;"></i> Point Marker</div>
                @else
                    <div class="lg" style="color:#777;">No data yet</div>
                    <div class="lg" style="color:#777;">No data yet</div>
                    <div class="lg" style="color:#777;">No data yet</div>
                    <div class="lg" style="color:#777;">No data yet</div>
                    <div class="lg" style="color:#777;">No data yet</div>
                    <div class="lg" style="color:#777;">No data yet</div>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div id="vmap"></div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const MAPDATA = @json($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

let centerLat = typeof MAPDATA.lat === 'number' ? MAPDATA.lat : 13.3597;
let centerLng = typeof MAPDATA.lng === 'number' ? MAPDATA.lng : 123.7311;
let map = L.map('vmap', {
    center: [centerLat, centerLng],
    zoom: MAPDATA.zoom || 8,
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

function calculateRiskPercentage(riskLevel) {
    const percentages = { Low: 20, Medium: 40, High: 70, Critical: 100 };
    return (percentages[riskLevel] || 0);
}
</script>
@endpush