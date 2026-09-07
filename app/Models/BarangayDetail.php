<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayDetail extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'barangay_id';

    public $timestamps = false;

    protected $fillable = [
        'barangay_id', 'logo', 'barangay_code', 'municipality', 'province', 'region', 'zip_code',
        'captain_name', 'councilors', 'secretary_name', 'treasurer_name', 'contact_info',
        'population', 'households', 'head_of_household', 'population_breakdown', 'boundaries',
        'streets', 'land_area', 'gps_coordinates', 'barangay_hall_address', 'health_center',
        'daycare_schools', 'community_centers', 'emergency_services', 'date_established',
        'website', 'ordinances', 'hazard_map', 'hazard_description', 'risk_level',
        'evacuation_routes', 'affected_areas', 'map_lat', 'map_lng', 'map_zoom',
        'hazard_polygons', 'hazard_points', 'hazard_map_updated_at', 'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'households' => 'integer',
            'head_of_household' => 'integer',
            'map_zoom' => 'integer',
            'hazard_map_updated_at' => 'datetime',
            'last_updated' => 'datetime',
        ];
    }
}
