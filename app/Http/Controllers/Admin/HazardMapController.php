<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MunicipalHazardMap;
use Illuminate\Support\Facades\Auth;

class HazardMapController extends Controller
{
    private const MAP_ID = 1;

    public function index()
    {
        return $this->showMap();
    }

    public function view()
    {
        return $this->showMap();
    }

    private function showMap()
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->hasPerm('view_hazard_map'), 403, 'You do not have permission to view the hazard map.');

        $map = MunicipalHazardMap::find(self::MAP_ID);
        $polygons = $map?->hazard_polygons
            ? json_decode($map->hazard_polygons, true, 512, JSON_THROW_ON_ERROR)
            : [];
        $points = $map?->hazard_points
            ? json_decode($map->hazard_points, true, 512, JSON_THROW_ON_ERROR)
            : [];

        return view('admin.malilipot-hazard-map', [
            'mapData' => [
                'lat' => $map?->map_lat ?? 13.3597,
                'lng' => $map?->map_lng ?? 123.7311,
                'zoom' => $map?->map_zoom ?? 7,
                'polygons' => $polygons,
                'points' => $points,
                'risk_level' => $map?->risk_level ?? 'Low',
                'updated_at' => $map?->hazard_map_updated_at?->format('M d, Y h:i A'),
                'total_polygons' => count($polygons),
                'total_points' => count($points),
            ],
        ]);
    }
}
