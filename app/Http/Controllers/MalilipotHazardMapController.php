<?php

namespace App\Http\Controllers;

use App\Models\MunicipalHazardMap;
use App\Support\MunicipalHazardMapData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MalilipotHazardMapController extends Controller
{
    private const MAP_ID = 1;

    public function edit()
    {
        $this->ensurePermission('view_hazard_map');

        return view('malilipot.hazard-map', [
            'mapData' => (object) $this->mapData(),
        ]);
    }

    public function view()
    {
        $this->ensurePermission('view_hazard_map');

        return view('malilipot.hazard-map-view', [
            'mapData' => $this->mapData(),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensurePermission('edit_hazard_map');
        $data = $this->validatedMapData($request);
        $map = MunicipalHazardMap::municipality();
        $map->fill($data);
        $map->hazard_map_updated_at = now();
        $map->save();

        return redirect()->route('malilipot.hazard_map')
            ->with('success', 'Malilipot municipality hazard map updated.');
    }

    private function ensurePermission(string $permission): void
    {
        $barangay = Auth::guard('barangay')->user();
        abort_unless($barangay && $barangay->hasPerm($permission), 403, 'You do not have permission to access the hazard map.');
    }

    private function mapData(): array
    {
        return MunicipalHazardMapData::load();
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
