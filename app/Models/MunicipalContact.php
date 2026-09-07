<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalContact extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'city_hotline', 'drrmo_hotline', 'police_hotline', 'fire_hotline', 'medical_services',
        'hospital_emergency', 'traffic_control', 'power_emergency', 'water_emergency',
        'ngo_relief', 'fire_volunteers', 'covid_hotline',
    ];
}
