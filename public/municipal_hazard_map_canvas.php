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
.map-stat { display: flex; justify-content: space-between; gap: 10x; padding: 10px 0; border-top: 1px solid #E9EFF5; color: #475569; font-size: .88rem; }
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

<script>
 // Google Maps API key
 const GOOGLE_MAPS_API_KEY = 'AIzaSyBELUPSX2pIIO-WCMu36lx3jbEkRssqYn0';

 // Load Google Maps API and initialize map
 function initMap() {
     const MAPDATA = <?= $mapJson ?>;
     
   // Malilipot, Albay center coordinates
   const malilipotLat = MAPDATA.lat || 13.3597;
   const malilipotLng = MAPDATA.lng || 123.7311;
   const defaultZoom = MAPDATA.zoom || 8;
   
   // Create map centered on Malilipot, Albay
   const map = new google.maps.Map(document.getElementById('municipal-hazard-map'), {
       center: { lat: malilipotLat, lng: malilipotLng },
       zoom: defaultZoom,
       mapTypeId: google.maps.MapTypeId.ROADMAP,
       disableDefaultUI: false,
       zoomControl: true
   });
   
   // Overlay hazard polygons
   (MAPDATA.polygons || []).forEach(function (polygon) {
       const color = polygon.color || '#0072C6';
       
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
               path.push({ lat: lat, lng: lng });
           }
           // Close the polygon
           path.push(path[0]);
       } else if (Array.isArray(polygon.points) && polygon.points.length) {
           // Use the polygon points directly
           path = polygon.points.map(function (p) {
               return { lat: Number(p[0]), lng: Number(p[1]) };
           });
           // Close the polygon
           if (path[0] && (path[0].lat !== path[path.length - 1].lat || path[0].lng !== path[path.length - 1].lng)) {
               path.push(path[0]);
           }
       }
       
       if (path && path.length >= 3) {
           const polygon = new google.maps.Polygon({
               paths: path,
               strokeColor: color,
               strokeWeight: 5,
               fillColor: color,
               fillOpacity: 0.25,
               map: map
           });
           
           // Add click event with popup
           const infoWindow = new google.maps.InfoWindow();
           const popupContent = `
               <strong>${polygon.name || 'Hazard zone'}</strong><br>
               ${polygon.risk || ''}
           `;
           
           polygon.addListener('click', function() {
               infoWindow.setContent(popupContent);
               infoWindow.open(map, polygon);
           });
       }
   });
   
   // Overlay hazard points/markers
   (MAPDATA.points || []).forEach(function (point) {
       if (!Number.isFinite(Number(point.lat)) || !Number.isFinite(Number(point.lng))) {
           return;
       }
       
       const marker = new google.maps.Marker({
           position: { lat: Number(point.lat), lng: Number(point.lng) },
           map: map,
           icon: {
               path: google.maps.SymbolPath.CIRCLE,
               fillColor: point.color || '#9013fe',
               fillOpacity: 1,
               strokeWeight: 2,
               scale: 5
           }
       });
       
       // Add click event with popup
       const infoWindow = new google.maps.InfoWindow();
       const popupContent = `
           <strong>${point.name || 'Hazard point'}</strong><br>
           ${point.risk || ''}
       `;
       
       marker.addListener('click', function() {
           infoWindow.setContent(popupContent);
           infoWindow.open(map, marker);
       });
   });
   
   // Fit bounds to include all hazards
   const bounds = new google.maps.LatLngBounds();
   bounds.extend({ lat: malilipotLat, lng: malilipotLng });
   
   (MAPDATA.polygons || []).forEach(function (polygon) {
       if (Array.isArray(polygon.points) && polygon.points.length) {
           polygon.points.forEach(function (p) {
               bounds.extend({ lat: Number(p[0]), lng: Number(p[1]) });
           });
       }
   });
   
   (MAPDATA.points || []).forEach(function (point) {
       if (!Number.isFinite(Number(point.lat)) || !Number.isFinite(Number(point.lng))) {
           return;
       }
       bounds.extend({ lat: Number(point.lat), lng: Number(point.lng) });
   });
   
   map.fitBounds(bounds, { maxZoom: 16 });
 }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBELUPSX2pIIO-WCMu36lx3jbEkRssqYn0&callback=initMap">
</script>