<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BarangayDetail;
use Illuminate\Http\Request;

class HazardMapController extends Controller
{
    public function index()
    {
        $barangays = Barangay::orderBy('barangay_name')->get();

        $rows = [];
        $riskCounts = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
        $riskLevels = ['Critical' => [], 'High' => [], 'Medium' => [], 'Low' => []];
        $atRisk = 0;
        $withMap = 0;
        $totalFeatures = 0;

        foreach ($barangays as $b) {
            $d = BarangayDetail::find($b->barangay_id);

            $polygons = $d && $d->hazard_polygons ? (@json_decode($d->hazard_polygons, true) ?: []) : [];
            $points = $d && $d->hazard_points ? (@json_decode($d->hazard_points, true) ?: []) : [];
            $featureCount = count($polygons) + count($points);

            $row = [
                'barangay_id' => $b->barangay_id,
                'barangay_name' => $b->barangay_name,
                'hazard_map' => $d->hazard_map ?? null,
                'hazard_description' => $d->hazard_description ?? null,
                'risk_level' => $d->risk_level ?? 'Low',
                'evacuation_routes' => $d->evacuation_routes ?? null,
                'affected_areas' => $d->affected_areas ?? null,
                'boundaries' => $d->boundaries ?? null,
                'map_lat' => $d->map_lat ?? null,
                'map_lng' => $d->map_lng ?? null,
                'map_zoom' => $d->map_zoom ?? 14,
                'hazard_polygons' => $d->hazard_polygons ?? null,
                'hazard_points' => $d->hazard_points ?? null,
                'hazard_map_updated_at' => $d && $d->hazard_map_updated_at ? $d->hazard_map_updated_at->toDateTimeString() : null,
                'feature_count' => $featureCount,
                'polygons' => $polygons,
                'points' => $points,
            ];

            if (in_array($row['risk_level'], ['High', 'Critical'])) $atRisk++;
            $hasLive = !empty($row['map_lat']) && !empty($row['map_lng']);
            if ($hasLive || !empty($row['hazard_polygons']) || !empty($row['hazard_points'])) $withMap++;
            $totalFeatures += $featureCount;

            $lvl = $row['risk_level'];
            if (!isset($riskCounts[$lvl])) $lvl = 'Low';
            $riskCounts[$lvl]++;
            $riskLevels[$lvl][] = $b->barangay_name;

            $rows[] = $row;
        }

        $totalBrgy = count($rows);

        return view('admin.hazard-map', compact(
            'rows', 'totalBrgy', 'atRisk', 'withMap', 'totalFeatures', 'riskCounts', 'riskLevels'
        ));
    }
}