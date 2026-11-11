<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MunicipalHazardMapData;
use Illuminate\Support\Facades\Auth;

class HazardMapController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.hazard_map.edit');
    }

    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->hasRole('admin') && $admin->hasPerm('edit_hazard_map'), 403, 'You do not have permission to edit the hazard map.');

        return view('malilipot.hazard-map', [
            'mapData' => MunicipalHazardMapData::load(),
        ]);
    }

    public function view()
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->hasPerm('view_hazard_map'), 403, 'You do not have permission to view the hazard map.');

        return view('admin.malilipot-hazard-map', [
            'mapData' => MunicipalHazardMapData::load(),
        ]);
    }
}
