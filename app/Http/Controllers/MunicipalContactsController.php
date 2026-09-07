<?php

namespace App\Http\Controllers;

use App\Models\MunicipalContact;
use Illuminate\Http\Request;

class MunicipalContactsController extends Controller
{
    protected $fields = [
        'city_hotline', 'drrmo_hotline', 'police_hotline', 'fire_hotline', 'medical_services',
        'hospital_emergency', 'traffic_control', 'power_emergency', 'water_emergency',
        'ngo_relief', 'fire_volunteers', 'covid_hotline',
    ];

    public function index()
    {
        $contact = MunicipalContact::first() ?? new MunicipalContact();
        return view('admin.municipal', ['contact' => $contact, 'fields' => $this->fields]);
    }

    public function update(Request $request)
    {
        $data = [];
        foreach ($this->fields as $f) {
            $data[$f] = $request->input($f) ? preg_replace('/\D/', '', $request->input($f)) : null;
        }

        $contact = MunicipalContact::first();
        if ($contact) {
            $contact->update($data);
        } else {
            MunicipalContact::create($data);
        }

        return back()->with('success', 'Municipal contacts updated successfully!');
    }
}
