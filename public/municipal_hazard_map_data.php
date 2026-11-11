<?php

function loadMunicipalHazardMapData(mysqli $connection): array
{
    $tableCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?
         LIMIT 1'
    );
    if (!$tableCheck) {
        throw new RuntimeException('Unable to check municipality hazard-map storage: ' . $connection->error);
    }

    $tableName = 'municipal_hazard_maps';
    $tableCheck->bind_param('s', $tableName);
    if (!$tableCheck->execute()) {
        throw new RuntimeException('Unable to check municipality hazard-map storage: ' . $tableCheck->error);
    }
    $hasMunicipalTable = (bool) $tableCheck->get_result()->fetch_row();
    $tableCheck->close();

    if (!$hasMunicipalTable) {
        throw new RuntimeException('Municipality hazard-map storage is unavailable. Run the database migrations.');
    }

    $statement = $connection->prepare(
        'SELECT map_lat, map_lng, map_zoom, risk_level, hazard_polygons, hazard_points, hazard_map_updated_at
         FROM municipal_hazard_maps
         WHERE id = 1
         LIMIT 1'
    );

    if (!$statement) {
        throw new RuntimeException('Unable to prepare the municipality hazard-map query: ' . $connection->error);
    }

    if (!$statement->execute()) {
        throw new RuntimeException('Unable to load municipality hazard-map data: ' . $statement->error);
    }

    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$row) {
        $row = [
            'map_lat' => 13.3597,
            'map_lng' => 123.7311,
            'map_zoom' => 7,
            'risk_level' => 'Low',
            'hazard_polygons' => null,
            'hazard_points' => null,
            'hazard_map_updated_at' => null,
        ];
    }

    $polygons = decodeMunicipalHazardMapFeatures($row['hazard_polygons'] ?? null, 'hazard polygons');
    $points = decodeMunicipalHazardMapFeatures($row['hazard_points'] ?? null, 'hazard points');
    $riskLevel = $row['risk_level'] ?? 'Low';
    if (!in_array($riskLevel, ['Low', 'Medium', 'High', 'Critical'], true)) {
        $riskLevel = 'Low';
    }

    return [
        'lat' => isset($row['map_lat']) ? (float) $row['map_lat'] : 13.3597,
        'lng' => isset($row['map_lng']) ? (float) $row['map_lng'] : 123.7311,
        'zoom' => isset($row['map_zoom']) ? max(1, min(20, (int) $row['map_zoom'])) : 7,
        'risk_level' => $riskLevel,
        'polygons' => $polygons,
        'points' => $points,
        'updated_at' => $row['hazard_map_updated_at'] ?? null,
        'total_polygons' => count($polygons),
        'total_points' => count($points),
        'has_data' => count($polygons) > 0 || count($points) > 0,
    ];
}

function decodeMunicipalHazardMapFeatures(?string $json, string $label): array
{
    if ($json === null || $json === '') {
        return [];
    }

    try {
        $features = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException('Stored municipality ' . $label . ' are not valid JSON.', 0, $exception);
    }

    if (!is_array($features) || !array_is_list($features)) {
        throw new RuntimeException('Stored municipality ' . $label . ' must be a JSON array.');
    }

    return $features;
}
