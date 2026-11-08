<?php

namespace App\Http\Controllers;

class BarangayHazardMapController extends Controller
{
    public function edit()
    {
        return redirect()->route('malilipot.hazard_map');
    }

    public function view()
    {
        // Redirect to Malilipot municipality hazard map view
        return redirect()->route('malilipot.hazard_map_view');
    }

    public function update()
    {
        return redirect()->route('malilipot.hazard_map')
            ->with('success', 'Hazard map saved. View the Malilipot municipality map.');
    }
}