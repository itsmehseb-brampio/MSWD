<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangayDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HazardMapApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $barangay = Auth::guard('barangay')->user();

        switch ($action) {
            case 'save':
                $detail = BarangayDetail::firstOrNew(['barangay_id' => $barangay->barangay_id]);

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

                return response()->json([
                    'ok' => true,
                    'success' => true,
                    'updated_at' => $detail->hazard_map_updated_at->format('M d, Y h:i A'),
                ]);

            case 'set_risk':
                $level = (string) $request->input('risk_level', '');
                if (!in_array($level, ['Low', 'Medium', 'High', 'Critical'], true)) {
                    return response()->json(['ok' => false, 'error' => 'Invalid risk level.'], 422);
                }
                $detail = BarangayDetail::firstOrNew(['barangay_id' => $barangay->barangay_id]);
                $detail->risk_level = $level;
                $detail->save();

                return response()->json(['ok' => true, 'success' => true, 'risk_level' => $level]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }

    private function jsonOrEmpty($value): string
    {
        $string = (string) $value;
        return is_array(json_decode($string, true)) ? $string : '[]';
    }
}