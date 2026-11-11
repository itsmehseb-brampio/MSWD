<?php

namespace App\Http\Controllers;

use App\Support\MunicipalHazardMapData;
use Illuminate\Support\Facades\Auth;

class MalilipotHazardMapController extends Controller
{
    public function edit()
    {
        return redirect()->route('malilipot.hazard_map_view');
    }

    public function view()
    {
        $this->ensurePermission('view_hazard_map');

        return view('malilipot.hazard-map-view', [
            'mapData' => $this->mapData(),
        ]);
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
}
