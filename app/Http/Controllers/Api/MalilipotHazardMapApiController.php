<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MunicipalHazardMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MalilipotHazardMapApiController extends Controller
{
    private const MAP_ID = 1;

    public function handle(Request $request, $action)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->hasRole('admin') || !$admin->hasPerm('edit_hazard_map')) {
            return response()->json(['ok' => false, 'error' => 'Only authorized administrators can edit the shared hazard map.'], 403);
        }

        switch ($action) {
            case 'save':
                $data = $this->validatedMapData($request);
                $map = MunicipalHazardMap::municipality();
                $map->fill($data);
                $map->hazard_map_updated_at = now();
                $map->save();

                return response()->json([
                    'ok' => true,
                    'success' => true,
                    'updated_at' => $map->hazard_map_updated_at->format('M d, Y h:i A'),
                ]);

            case 'set_risk':
                $data = $request->validate([
                    'risk_level' => ['required', 'in:Low,Medium,High,Critical'],
                ]);
                $map = MunicipalHazardMap::municipality();
                $map->risk_level = $data['risk_level'];
                $map->hazard_map_updated_at = now();
                $map->save();

                return response()->json([
                    'ok' => true,
                    'success' => true,
                    'risk_level' => $map->risk_level,
                ]);

            case 'get_data':
                $map = MunicipalHazardMap::find(self::MAP_ID);
                $polygons = $map?->hazard_polygons
                    ? json_decode($map->hazard_polygons, true, 512, JSON_THROW_ON_ERROR)
                    : [];
                $points = $map?->hazard_points
                    ? json_decode($map->hazard_points, true, 512, JSON_THROW_ON_ERROR)
                    : [];
                $hasData = count($polygons) > 0 || count($points) > 0;

                return response()->json([
                    'ok' => true,
                    'data' => [
                        'lat' => $map?->map_lat ?? 13.3597,
                        'lng' => $map?->map_lng ?? 123.7311,
                        'zoom' => $map?->map_zoom ?? 7,
                        'polygons' => $polygons,
                        'points' => $points,
                        'risk_level' => $map?->risk_level ?? 'Low',
                        'updated_at' => $map?->hazard_map_updated_at?->format('M d, Y h:i A') ?? 'No hazard map data yet',
                        'total_polygons' => count($polygons),
                        'total_points' => count($points),
                        'has_data' => $hasData,
                    ],
                ]);

            default:
                return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
        }
    }

    private function validatedMapData(Request $request): array
    {
        $data = $request->validate([
            'map_lat' => ['required', 'numeric', 'between:-90,90'],
            'map_lng' => ['required', 'numeric', 'between:-180,180'],
            'map_zoom' => ['required', 'integer', 'between:1,20'],
            'hazard_polygons' => ['required', 'string', 'json'],
            'hazard_points' => ['required', 'string', 'json'],
        ]);

        foreach (['hazard_polygons', 'hazard_points'] as $field) {
            $features = json_decode($data[$field], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($features) || !array_is_list($features)) {
                throw ValidationException::withMessages([
                    $field => 'The hazard features must be a JSON array.',
                ]);
            }
        }

        return [
            'map_lat' => (float) $data['map_lat'],
            'map_lng' => (float) $data['map_lng'],
            'map_zoom' => (int) $data['map_zoom'],
            'hazard_polygons' => $data['hazard_polygons'],
            'hazard_points' => $data['hazard_points'],
        ];
    }
}
