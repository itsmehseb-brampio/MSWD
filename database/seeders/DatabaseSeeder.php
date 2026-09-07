<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Barangay;
use App\Models\DisasterFormatField;
use App\Models\MunicipalContact;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    protected $barangays = [
        'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Barangay V',
        'Binitayan', 'Calbayog', 'Canaway', 'Salvacion', 'San Antonio Santicon',
        'San Antonio Sulong', 'San Francisco', 'San Isidro Ilawod', 'San Isidro Iraya',
        'San Jose', 'San Roque', 'Santa Cruz', 'Santa Teresa',
    ];

    protected $disasterFields = [
        ['Disaster Type', 'disaster_type', 'select', "Typhoon\nFlood\nEarthquake\nLandslide\nFire\nVolcanic\nOthers", 1],
        ['Head of Household', 'household_head', 'text', '', 1],
        ['Number of Family Members', 'family_members', 'number', '', 0],
        ['Full Address', 'full_address', 'textarea', '', 1],
        ['Housing Type', 'housing_type', 'text', '', 0],
        ['Extent of Damage', 'damage_extent', 'select', "None\nPartially\nTotally", 1],
        ['Description / Additional Details', 'description', 'textarea', '', 0],
    ];

    public function run(): void
    {
        if (Admin::where('username', 'admin')->doesntExist()) {
            Admin::create([
                'username' => 'admin',
                'password' => 'admin123',
            ]);
        }

        foreach ($this->barangays as $name) {
            if (!Barangay::where('barangay_name', $name)->exists()) {
                Barangay::create([
                    'barangay_name' => $name,
                    'address' => 'Brgy. ' . $name . ', Malilipot, Albay',
                    'password' => 'barangay123',
                    'disaster_open' => 1,
                ]);
            }
        }

        if (!MunicipalContact::exists()) {
            MunicipalContact::create([
                'city_hotline' => '09171234567',
                'drrmo_hotline' => '09181234567',
                'police_hotline' => '09191234567',
                'medical_services' => '09201234567',
                'hospital_emergency' => '09211234567',
            ]);
        }

        if (DisasterFormatField::count() === 0) {
            foreach ($this->disasterFields as $i => $f) {
                DisasterFormatField::create([
                    'field_label' => $f[0],
                    'field_name' => $f[1],
                    'field_type' => $f[2],
                    'field_options' => $f[3],
                    'is_required' => $f[4],
                    'field_order' => $i + 1,
                ]);
            }
        }
    }
}
