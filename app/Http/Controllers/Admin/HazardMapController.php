<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MunicipalHazardMapData;
use Illuminate\Support\Facades\Auth;

class HazardMapController extends Controller
{
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

        return view('admin.hazard-map', [
            'mapData' => MunicipalHazardMapData::load(),
        ]);
    }
}
