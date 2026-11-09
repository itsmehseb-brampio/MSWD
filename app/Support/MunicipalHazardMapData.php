<?php

namespace App\Support;

use App\Models\MunicipalHazardMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MunicipalHazardMapData
{
    private const MAP_ID = 1;

    public static function load(): array
    {
        if (Schema::hasTable('municipal_hazard_maps')) {
            $map = MunicipalHazardMap::find(self::MAP_ID);
            if ($map) {
                $polygons = self::decodeFeatures($map->hazard_polygons, 'municipality hazard polygons');
                $points = self::decodeFeatures($map->hazard_points, 'municipality hazard points');

                // If no boundary exists yet, add a default Malilipot municipality boundary
                $hasBoundary = $polygons->some(function (p) { return $p['kind'] === 'boundary'; });
                if (!$hasBoundary) {
                    $malilipotBoundary = [
                        'id' => 'poly-0',
                        'kind' => 'boundary',
                        'name' => 'Malilipot Municipality',
                        'ptype' => 'polygon',
                        'color' => '#0072C6',
                        'type' => 'freehand',
                        'points' => [
                            [13.3000, 123.6800],
                            [13.3800, 123.6800],
                            [13.3800, 123.7800],
                            [13.3000, 123.7800],
                            [13.3000, 123.6800],
                        ]
                    ];
                    $polygons = array_merge([$malilipotBoundary], (array) $polygons);
                }

                return self::formatData(
                    $map->map_lat ?? 13.3597,
                    $map->map_lng ?? 123.7311,
                    $map->map_zoom ?? 7,
                    $map->risk_level ?? 'Low',
                    $polygons,
                    $points,
                    $map->hazard_map_updated_at?->format('M d, Y h:i A')
                );
            }
        }

        return self::loadFromBarangayMaps();
    }

    private static function loadFromBarangayMaps(): array
    {
        $rows = DB::table('barangays')
            ->leftJoin('barangay_details', 'barangay_details.barangay_id', '=', 'barangays.barangay_id')
            ->select([
                'barangays.barangay_name',
                'barangay_details.map_lat',
                'barangay_details.map_lng',
                'barangay_details.risk_level',
                'barangay_details.hazard_polygons',
                'barangay_details.hazard_points',
                'barangay_details.hazard_map_updated_at',
            ])
            ->get();

        $polygons = [];
        $points = [];
        $latitudes = [];
        $longitudes = [];
        $riskLevels = [];
        $updatedAt = null;

        foreach ($rows as $row) {
            foreach (self::decodeFeatures($row->hazard_polygons, 'barangay hazard polygons') as $polygon) {
                $polygon['barangay_name'] = $row->barangay_name;
                $polygons[] = $polygon;
            }

            foreach (self::decodeFeatures($row->hazard_points, 'barangay hazard points') as $point) {
                $point['barangay_name'] = $row->barangay_name;
                $points[] = $point;
            }

            if (is_numeric($row->map_lat) && is_numeric($row->map_lng)) {
                $latitudes[] = (float) $row->map_lat;
                $longitudes[] = (float) $row->map_lng;
            }
            if (in_array($row->risk_level, ['Low', 'Medium', 'High', 'Critical'], true)) {
                $riskLevels[] = $row->risk_level;
            }
            if ($row->hazard_map_updated_at && (!$updatedAt || $row->hazard_map_updated_at > $updatedAt)) {
                $updatedAt = $row->hazard_map_updated_at;
            }
        }

        $riskLevel = 'Low';
        foreach (['Critical', 'High', 'Medium', 'Low'] as $candidate) {
            if (in_array($candidate, $riskLevels, true)) {
                $riskLevel = $candidate;
                break;
            }
        }

        return self::formatData(
            $latitudes ? array_sum($latitudes) / count($latitudes) : 13.3597,
            $longitudes ? array_sum($longitudes) / count($longitudes) : 123.7311,
            7,
            $riskLevel,
            $polygons,
            $points,
            $updatedAt ? Carbon::parse($updatedAt)->format('M d, Y h:i A') : null
        );
    }

    private static function decodeFeatures(?string $json, string $label): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $features = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Stored ' . $label . ' are not valid JSON.', 0, $exception);
        }

        if (!is_array($features) || !array_is_list($features)) {
            throw new RuntimeException('Stored ' . $label . ' must be a JSON array.');
        }

        return $features;
    }

    private static function formatData(
        float|int $lat,
        float|int $lng,
        int $zoom,
        string $riskLevel,
        array $polygons,
        array $points,
        ?string $updatedAt
    ): array {
        if (!in_array($riskLevel, ['Low', 'Medium', 'High', 'Critical'], true)) {
            $riskLevel = 'Low';
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
            'zoom' => max(1, min(20, $zoom)),
            'risk_level' => $riskLevel,
            'polygons' => $polygons,
            'points' => $points,
            'updated_at' => $updatedAt,
            'total_polygons' => count($polygons),
            'total_points' => count($points),
            'has_data' => count($polygons) > 0 || count($points) > 0,
        ];
    }
}
