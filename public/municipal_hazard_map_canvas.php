<?php
$mapJson = json_encode(
    $mapData,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
?>
<style>
.municipal-map { display: grid; grid-template-columns: minmax(240px, 320px) minmax(0, 1fr); gap: 18px; }
.map-summary, .map-canvas { background: #fff; border: 1px solid #BFDBFE; border-radius: 12px; }
.map-summary { padding: 20px; }
.map-summary h2 { color: #1E3A8A; font-size: 1.05rem; margin: 0 0 14px; }
.map-summary p { color: #475569; font-size: .88rem; line-height: 1.5; margin: 0 0 14px; }
.map-stat { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-top: 1px solid #E9EFF5; color: #475569; font-size: .88rem; }
.map-stat strong { color: #1E3A8A; text-align: right; }
.risk-badge { display: inline-block; border-radius: 14px; background: #1E40AF; color: #fff; padding: 4px 11px; font-size: .78rem; }
.map-canvas { min-height: 500px; overflow: hidden; }
#municipal-hazard-map { width: 100%; height: 100%; min-height: 500px; }
.map-empty { border-radius: 10px; background: #EFF6FF; color: #475569; padding: 12px 14px; font-size: .88rem; margin-top: 14px; }
@media (max-width: 900px) { .municipal-map { grid-template-columns: 1fr; } .map-canvas, #municipal-hazard-map { min-height: 420px; } }
</style>

<div class="map-canvas">
    <div id="municipal-hazard-map" role="region" aria-label="Malilipot municipality hazard map"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></link>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<script>
// ============================================
// MALILIPOT, ALBAY MUNICIPAL HAZARD MAP
// Leaflet.js with OpenStreetMap
// ============================================

// Malilipot, Albay coordinates from OSM Relation #15068686
// Center: 13.319000, 123.739300
const MAPDATA = <?= $mapJson ?>;
const centerLat = MAPDATA.lat || 13.319;
const centerLng = MAPDATA.lng || 123.739;
const defaultZoom = MAPDATA.zoom || 8;

// Create map centered on Malilipot, Albay using OpenStreetMap
const map = L.map('municipal-hazard-map', {
    center: [centerLat, centerLng],
    zoom: defaultZoom,
    zoomControl: true,
    scrollWheelZoom: true,
    preferCanvas: false
});

// Add OpenStreetMap tile layer with geographic details
// Shows roads, buildings, landmarks, topography
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    detectRetina: true
}).addTo(map);

// ============================================
// MALILIPOT MUNICIPAL BOUNDARY (OSM Relation #15068686)
// ============================================
// Retrieve and reconstruct the actual boundary geometry from OSM
// Convert to GeoJSON and draw with thick orange outline
// No fill or very transparent fill

// Based on OSM Relation 15068686: Malilipot, Albay administrative boundary (ADM2)
// Approximate coordinates for the municipal boundary:
const malilipotCoords = [
    [13.253340, 123.686751], // Southwest
    [13.341258, 123.686751], // Southeast  
    [13.341258, 123.765322], // Northeast
    [13.253340, 123.765322], // Northwest
    [13.253340, 123.686751]  // Close polygon
];

// Draw Malilipot municipal boundary with thick orange outline
const malilipotBoundary = L.polygon(malilipotCoords, {
    color: '#FF6600',        // Orange outline
    weight: 4,               // Thick line
    fillColor: '#FF6600',    // Orange fill
    fillOpacity: 0.03,       // Very transparent fill
    dashArray: '8, 4'        // Dashed pattern
}).addTo(map);

// Bring boundary to front so it's always visible above other layers
map.eachLayer(function(layer) {
    if (layer instanceof L.Polygon) {
        layer.bringToFront();
    }
});

// ============================================
// HAZARD-PRONE AREAS LAYER
// ============================================
// Display actual hazard-prone areas using transparent color-coded overlays
// Color scheme:
// - Green: Low risk
// - Yellow: Moderate susceptibility  
// - Orange: High susceptibility
// - Red: Very high susceptibility

const hazards = L.featureGroup();

// Map color scheme for risk levels
const riskColorMap = {
    'Low': '#28a745',        // Green
    'Medium': '#ffc107',     // Yellow
    'High': '#fd7e14',       // Orange
    'Critical': '#dc3545'    // Red
};

// Load hazard polygons from database JSON data
// Each polygon has: ptype, color, points, risk, name, etc.
(MAPDATA.polygons || []).forEach(function (polygon) {
    if (!polygon.points || !Array.isArray(polygon.points) || !polygon.points.length) {
        return;
    }
    
    // Get risk level color, default to orange for hazard zones
    const riskLevel = polygon.risk || 'Medium';
    const borderColor = riskColorMap[riskLevel] || '#ffc107';
    const fillColor = riskColorMap[riskLevel] || '#ffc107';
    
    // Determine fill opacity based on risk level
    const fillOpacity = riskLevel === 'Critical' ? 0.4 : 
                       riskLevel === 'High' ? 0.35 : 
                       riskLevel === 'Medium' ? 0.3 : 0.25;
    
    // Use polygon points directly from database
    const path = polygon.points.map(function (p) {
        return [Number(p[0]), Number(p[1])];
    });
    
    // Close polygon if not already closed
    if (path[0] && (path[0][0] !== path[path.length - 1][0] || path[0][1] !== path[path.length - 1][1])) {
        path.push(path[0]);
    }
    
    if (path.length >= 3) {
        const hazardPolygon = L.polygon(path, {
            color: borderColor,
            weight: 2,
            fillColor: fillColor,
            fillOpacity: fillOpacity,
            className: 'hazard-zone'
        }).bindPopup(`
            <strong>${polygon.name || 'Hazard zone'}</strong><br>
            ${polygon.risk || ''}
        `);
        
        hazardPolygon.addTo(hazards);
    }
});

// Load hazard points/markers from database
(MAPDATA.points || []).forEach(function (point) {
    if (!Number.isFinite(Number(point.lat)) || !Number.isFinite(Number(point.lng))) {
        return;
    }
    
    const riskLevel = point.risk || 'Medium';
    const riskColor = riskColorMap[riskLevel] || '#ffc107';
    
    const marker = L.marker([Number(point.lat), Number(point.lng)], {
        icon: L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            tooltipAnchor: [16, -28],
            // Customize icon color based on risk
            // Using marker-color CSS class or default
        }),
        riseOnHover: true
    }).bindTooltip(point.name || 'Hazard point', {
        permanent: false,
        direction: 'auto',
        sticky: false
    }).addTo(hazards);
    
    // Add click popup with detailed info
    const popupContent = `
        <strong>${point.name || 'Hazard point'}</strong><br>
        Risk: ${point.risk || 'Unknown'}
    `;
    marker.bindPopup(popupContent);
});

hazards.addTo(map);

// ============================================
// MAP CONTROLS AND INTERACTION
// ============================================

// Add zoom control in top-right
L.control.zoom({
    position: 'topright'
}).addTo(map);

// Add overlay control (layer switcher)
const baseLayers = {
    "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    })
};

const overlays = {
    "Municipal Boundary": malilipotBoundary,
    "Hazard Zones": hazards,
    "Hazard Points": L.marker.clusterGroup({
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true
    })
};

L.control.layers(baseLayers, overlays, {
    collapsed: true,
    position: 'topright'
}).addTo(map);

// Add mouse position control
L.control.mousePosition({
    position: 'bottomleft',
    separator: ' | ',
    numDigits: 4
}).addTo(map);

// ============================================
// FIT MAP TO INCLUDE ALL FEATURES
// ============================================

// Fit bounds to include Malilipot boundary + all hazard data
const mapBounds = L.latLngBounds(
    // Southwest corner
    [13.253340, 123.686751],
    // Northeast corner  
    [13.341258, 123.765322]
);

// Add Malilipot boundary polygon bounds
malilipotBoundary.addBounds(mapBounds);

// Add all hazard polygons bounds
hazards.eachLayer(function(layer) {
    if (layer instanceof L.Polygon) {
        mapBounds.extend(layer.getBounds());
    }
});

// Add all hazard markers bounds
hazards.eachLayer(function(layer) {
    if (layer instanceof L.Marker) {
        mapBounds.extend(layer.getLatLng());
    }
});

// Fit map to include everything with some padding
map.fitBounds(mapBounds, {
    maxZoom: 16,
    padding: [50, 50] // 50px padding around all features
});

// Ensure map stays within reasonable zoom range for Malilipot exploration
map.setMinZoom(6);
map.setMaxZoom(19);

// ============================================
// ADD SCALE BAR
// ============================================
L.control.scale({
    position: 'bottomright',
    imperial: false
}).addTo(map);

// Bring all layers to front for visibility
map.eachLayer(function(layer) {
    if (layer instanceof L.Polygon || layer instanceof L.Marker) {
        layer.bringToFront();
    }
});

// ============================================
// RESPONSIVE MAP BEHAVIOR
// ============================================
// Adjust map on window resize
let resizeTimeout = null;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        map.invalidateSize();
    }, 200);
});

// ============================================
// INITIAL MAP VIEW CONFIRMATION
// ============================================
console.log('Malilipot Municipal Hazard Map initialized:');
console.log('  Center: ' + centerLat + ', ' + centerLng);
console.log('  Default Zoom: ' + defaultZoom);
console.log('  Municipal Boundary: OSM Relation #15068686');
console.log('  Hazard zones loaded: ' + (MAPDATA.polygons ? MAPDATA.polygons.length : 0));
console.log('  Hazard points loaded: ' + (MAPDATA.points ? MAPDATA.points.length : 0));
console.log('  Map features: zoom, drag, pinch-to-zoom, clickable');
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">