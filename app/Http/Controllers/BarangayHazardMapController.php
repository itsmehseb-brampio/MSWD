<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayHazardMapController extends Controller
{
    public function edit()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('edit_hazard_map') && !$barangay->hasPerm('view_hazard_map')) {
            abort(403, 'You do not have permission to view/edit the hazard map.');
        }

        return view('barangay.hazard-map', ['mapData' => $this->mapData($barangay)]);
    }

    public function view()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('view_hazard_map')) {
            abort(403, 'You do not have permission to view the hazard map.');
        }

        return view('barangay.hazard-map-view', ['mapData' => $this->mapData($barangay)]);
    }

    public function update(Request $request)
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('edit_hazard_map')) {
            abort(403, 'You do not have permission to edit the hazard map.');
        }
        $detail = BarangayDetail::firstOrNew(['barangay_id' => $barangay->barangay_id]);

        $this->applyMapPayload($detail, $request);

        return redirect()->route('barangay.hazard_map')
            ->with('success', 'Hazard map saved successfully!');
    }

    private function mapData($barangay): array
    {
        $detail = BarangayDetail::where('barangay_id', $barangay->barangay_id)->first();

        return [
            'lat' => $detail && $detail->map_lat !== null ? (float) $detail->map_lat : null,
            'lng' => $detail && $detail->map_lng !== null ? (float) $detail->map_lng : null,
            'zoom' => $detail && $detail->map_zoom ? (int) $detail->map_zoom : 14,
            'polygons' => $detail ? (json_decode((string) $detail->hazard_polygons, true) ?: []) : [],
            'points' => $detail ? (json_decode((string) $detail->hazard_points, true) ?: []) : [],
            'risk_level' => $detail->risk_level ?? 'Low',
            'updated_at' => $detail && $detail->hazard_map_updated_at
                ? $detail->hazard_map_updated_at->format('F j, Y, g:i A')
                : 'Not saved yet',
            'barangay_name' => $barangay->barangay_name,
        ];
    }

    private function applyMapPayload(BarangayDetail $detail, Request $request): void
    {
        $lat = $request->input('map_lat');
        $lng = $request->input('map_lng');
        $zoom = (int) $request->integer('map_zoom', 14);
        if ($zoom < 1 || $zoom > 20) {
            $zoom = 14;
        }

        $detail->map_lat = (is_numeric($lat) && abs((float) $lat) <= 90) ? (float) $lat : null;
        $detail->map_lng = (is_numeric($lng) && abs((float) $lng) <= 180) ? (float) $lng : null;
        $detail->map_zoom = $zoom;
        $detail->hazard_polygons = $this->jsonOrEmpty($request->input('hazard_polygons'));
        $detail->hazard_points = $this->jsonOrEmpty($request->input('hazard_points'));
        $detail->hazard_map_updated_at = now();
        $detail->last_updated = now();
        $detail->save();
    }

    private function jsonOrEmpty($value): string
    {
        $string = (string) $value;
        return is_array(json_decode($string, true)) ? $string : '[]';
    }
}