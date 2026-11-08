<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalHazardMap extends Model
{
    private const MUNICIPALITY_ID = 1;

    protected $table = 'municipal_hazard_maps';

    public $timestamps = false;

    protected $fillable = [
        'map_lat',
        'map_lng',
        'map_zoom',
        'risk_level',
        'hazard_polygons',
        'hazard_points',
        'hazard_map_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'map_lat' => 'float',
            'map_lng' => 'float',
            'map_zoom' => 'integer',
            'hazard_map_updated_at' => 'datetime',
        ];
    }

    public static function municipality(): self
    {
        $map = static::firstOrNew(['id' => self::MUNICIPALITY_ID]);
        if (!$map->exists) {
            $map->setAttribute('id', self::MUNICIPALITY_ID);
        }

        return $map;
    }
}
