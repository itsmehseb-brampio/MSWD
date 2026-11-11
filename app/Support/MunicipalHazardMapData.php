<?php

namespace App\Support;

use App\Models\MunicipalHazardMap;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MunicipalHazardMapData
{
    private const MAP_ID = 1;

    public static function load(): array
    {
        if (!Schema::hasTable('municipal_hazard_maps')) {
            throw new RuntimeException('Municipality hazard-map storage is unavailable. Run the database migrations.');
        }

        $map = MunicipalHazardMap::find(self::MAP_ID);
        if (!$map) {
            return self::formatData(13.3597, 123.7311, 7, 'Low', [], [], null);
        }

        $polygons = self::decodeFeatures($map->hazard_polygons, 'municipality hazard polygons');
        $points = self::decodeFeatures($map->hazard_points, 'municipality hazard points');

        if (!array_filter($polygons, fn ($polygon) => is_array($polygon) && ($polygon['kind'] ?? null) === 'boundary')) {
            array_unshift($polygons, [
                'id' => 'poly-0',
                'kind' => 'boundary',
                'name' => 'Malilipot Municipality Boundary',
                'ptype' => 'polygon',
                'color' => '#0072C6',
                'type' => 'freehand',
                'points' => [
                    [13.3000, 123.6800],
                    [13.3800, 123.6800],
                    [13.3800, 123.7800],
                    [13.3000, 123.7800],
                    [13.3000, 123.6800],
                ],
            ]);
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
