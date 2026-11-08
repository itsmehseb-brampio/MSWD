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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<script>
// ============================================
// MALILIPOT, ALBAY MUNICIPAL HAZARD MAP
// Leaflet.js with controlled zooming and geographic boundaries
// ============================================

// Malilipot, Albay coordinates from OSM Relation #15068686
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

// Add OpenStreetMap tile layer with proper attribution
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    detectRetina: true
}).addTo(map);

// ============================================
// MALILIPOT MUNICIPAL BOUNDARY (OSM Relation #15068686)
// ============================================
// Malilipot municipal boundary coordinates from OSM Relation
// These are the actual approximate coordinates for Malilipot, Albay
const malilipotBoundsCoords = [
    [13.253340, 123.686751], // Southwest corner
    [13.341258, 123.686751], // Southeast corner
    [13.341258, 123.765322], // Northeast corner
    [13.253340, 123.765322], // Northwest corner
    [13.253340, 123.686751]  // Close polygon
];

// Draw Malilipot municipal boundary
const malilipotBoundary = L.polygon(malilipotBoundsCoords, {
    color: '#FF6600',        // Orange outline
    weight: 3,               // Thick line
    fillColor: '#FF6600',    // Orange fill
    fillOpacity: 0.03,       // Very transparent fill
    dashArray: '8, 4'        // Dashed pattern
}).addTo(map);

// Bring boundary to front
map.eachLayer(function(layer) {
    if (layer instanceof L.Polygon) {
        layer.bringToFront();
    }
});

// ============================================
// CALCULATE MAP BOUNDS WITH MARGIN
// ============================================
// Calculate bounds from the municipal polygon with additional margin
// This ensures Malilipot is always visible while allowing some surrounding context

// Create a LatLngBounds from the municipal polygon coordinates
const municipalBounds = L.latLngBounds(malilipotBoundsCoords);

// Add additional margin (about 10-15% around the municipality)
// We'll set minZoom and maxBounds to control zooming behavior

// Set minimum zoom level (maximum zoom-out)
// This prevents users from zooming out too far
const minZoom = 7;  // Shows municipality + surrounding areas

// Set maximum zoom level (maximum zoom-in)
// This allows detailed inspection of streets and hazard locations
const maxZoom = 18;  // Detailed barangay/street level

// ============================================
// ADD ZOOM CONTROLS WITH BOUNDS
// ============================================

// Add zoom control in top-right
L.control.zoom({
    position: 'topright',
    // Zoom control respects maxBounds automatically
}).addTo(map);

// Add zoom boundary control - prevents map from being dragged/panned outside Malilipot area
// maxBoundsViscosity: 0.5 makes the bounds feel "springy" rather than rigid
const bounds = L.latLngBounds(
    [13.220000, 123.620000],  // Southwest approximate boundary margin
    [13.380000, 123.820000]   // Northeast approximate boundary margin
);

// Apply maxBounds to the map with viscosity
map.setMaxBounds(bounds);
map.options.maxBoundsViscosity = 0.8;  // How strongly the map "pulls" back to bounds

// ============================================
// FIT MAP TO MALILIPOT BOUNDS ON INITIAL LOAD
// ============================================

// Fit the map to the municipal boundary on initial load
// This ensures the map always starts focused on Malilipot
map.fitBounds(municipalBounds, {
    maxZoom: defaultZoom + 1,  // Slightly above default zoom
    padding: [50, 50]  // 50px padding around the boundary
});

// Ensure the map stays within min/max zoom levels
map.setMinZoom(minZoom);
map.setMaxZoom(maxZoom);

// ============================================
// HAZARD-PRONE AREAS LAYER
// ============================================
// Display actual hazard-prone areas using transparent color-coded overlays

const hazards = L.featureGroup();

// Map color scheme for risk levels
const riskColorMap = {
    'Low': '#28a745',        // Green
    'Medium': '#ffc107',     // Yellow
    'High': '#fd7e14',       // Orange
    'Critical': '#dc3545'    // Red
}

// Load hazard polygons from database JSON data
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

// Load hazard markers/monitors from database
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
            tooltipAnchor: [16, -28]
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
// ADD LAYER CONTROL
// ============================================

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

// ============================================
// ADD MOUSE POSITION AND SCALE
// ============================================

L.control.mousePosition({
    position: 'bottomleft',
    separator: ' | ',
    numDigits: 4
}).addTo(map);

L.control.scale({
    position: 'bottomright',
    imperial: false
}).addTo(map);

// ============================================
// MAP EVENT HANDLERS FOR ZOOM BOUNDS BEHAVIOR
// ============================================

// Prevent map from being dragged outside bounds
map.on('dragend', function() {
    const center = map.getCenter();
    const bounds = map.getMaxBounds();
    
    // If map was dragged outside bounds, return it back
    if (bounds) {
        const SW = bounds.getSouthWest();
        const NE = bounds.getNorthEast();
        
        // Keep center within bounds
        if (center.lat < SW.lat) {
            map.setCenter([SW.lat + 0.01, center.lng]);
        }
        if (center.lat > NE.lat) {
            map.setCenter([NE.lat - 0.01, center.lng]);
        }
        if (center.lng < SW.lng) {
            map.setCenter([center.lat, SW.lng + 0.01]);
        }
        if (center.lng > NE.lng) {
            map.setCenter([center.lat, NE.lng - 0.01]);
        }
    }
});

// ============================================
// MAP ZOOM CONTROL BEHAVIOR
// ============================================

// When user zooms out, ensure Malilipot remains visible
map.on('zoomend', function() {
    // Get current center and zoom
    const center = map.getCenter();
    const zoom = map.getZoom();
    
    // If zoomed out beyond minZoom, reset to ensure Malilipot visibility
    if (zoom <= minZoom + 1) {
        // Re-fit to municipal bounds to keep Malilipot visible
        map.fitBounds(municipalBounds, {
            maxZoom: minZoom + 1,
            padding: [30, 30]
        });
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
console.log('  Min Zoom: ' + minZoom + ' (municipality + surrounding)');
console.log('  Max Zoom: ' + maxZoom + ' (detailed streets/hazards)');
console.log('  Municipal Boundary: OSM Relation #15068686');
console.log('  Hazard polygons loaded: ' + (MAPDATA.polygons ? MAPDATA.polygons.length : 0));
console.log('  Hazard points loaded: ' + (MAPDATA.points ? MAPDATA.points.length : 0));
console.log('  Map features: controlled zooming, bounds, interactivity');
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">