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
// Initialize map on Malilipot, Albay using OpenStreetMap
// Malilipot, Albay coordinates: approximately 13.319° N, 123.739° E
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
 
 // Fit map to show Malilipot municipality properly
 // Based on OSM relation #15068686 - Malilipot, Albay administrative boundary
 // The map will automatically fit to include the Malilipot area
 // with appropriate zoom level for municipal exploration
 map.setZoom(defaultZoom);
 
 // Overlay hazard polygons with red dashed boundaries
 const hazards = L.featureGroup();
 
 (MAPDATA.polygons || []).forEach(function (polygon) {
     const color = polygon.color || '#DC3545'; // Red for hazard zones
     
     let path = [];
     if (polygon.ptype === 'circle' && polygon.center) {
         // Circle - display as polygon with coordinates around the center
         const centerLat = Number(polygon.center.lat);
         const centerLng = Number(polygon.center.lng);
         const radius = Number(polygon.radius) || 300;
         
         // Convert radius to approximate degrees (1 degree ~ 111km at equator)
         const radiusDeg = radius / 111000;
         
         const numPoints = 32;
         for (let i = 0; i < numPoints; i++) {
             const angle = (i / numPoints) * 2 * Math.PI;
             const lat = centerLat + (radiusDeg * Math.cos(angle));
             const lng = centerLng + (radiusDeg / Math.cos(centerLat * Math.PI / 180) * Math.sin(angle));
             path.push([lat, lng]);
         }
         // Close the polygon
         path.push(path[0]);
     } else if (Array.isArray(polygon.points) && polygon.points.length) {
         // Use the polygon points directly
         path = polygon.points.map(function (p) {
             return [Number(p[0]), Number(p[1])];
         });
         // Close the polygon if not already closed
         if (path[0] && (path[0][0] !== path[path.length - 1][0] || path[0][1] !== path[path.length - 1][1])) {
             path.push(path[0]);
         }
     }
     
     if (path && path.length >= 3) {
         const hazardPolygon = L.polygon(path, {
             color: color,
             weight: 3,
             fillColor: color,
             fillOpacity: 0.15,
             dashArray: '5, 5', // Dashed boundary
             className: 'hazard-zone'
         }).bindPopup(`
             <strong>${polygon.name || 'Hazard zone'}</strong><br>
             ${polygon.risk || ''}
         `);
         
         hazardPolygon.addTo(hazards);
     }
 });
 
 // Overlay hazard markers with warning icons and labels
 (MAPDATA.points || []).forEach(function (point) {
     if (!Number.isFinite(Number(point.lat)) || !Number.isFinite(Number(point.lng))) {
         return;
     }
     
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
     
     // Add click popup
     const popupContent = `
         <strong>${point.name || 'Hazard point'}</strong><br>
         ${point.risk || ''}
     `;
     marker.bindPopup(popupContent);
 });
 
 hazards.addTo(map);
 
 // Add zoom control in top-right
 L.control.zoom({
     position: 'topright'
 }).addTo(map);
 
 // Add layer control
 const baseLayers = {
     "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
         maxZoom: 19,
         attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
     })
 };
 const overlays = {
     "Hazard Zones": hazards,
     "Hazard Points": L.marker.clusterGroup({spiderfyOnMaxZoom: true, showCoverageOnHover: true})
 };
 L.control.layers(baseLayers, overlays, { collapsed: true }).addTo(map);
 
 // Add mouse position control
 L.control.mousePosition({
     position: 'bottomleft',
     separator: ' | '
 }).addTo(map);
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">