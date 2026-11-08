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
        return loadLegacyMunicipalHazardMapData($connection);
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
        return loadLegacyMunicipalHazardMapData($connection);
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

function loadLegacyMunicipalHazardMapData(mysqli $connection): array
{
    $result = $connection->query(
        'SELECT barangays.barangay_name, barangay_details.map_lat, barangay_details.map_lng,
                barangay_details.map_zoom, barangay_details.risk_level,
                barangay_details.hazard_polygons, barangay_details.hazard_points,
                barangay_details.hazard_map_updated_at
         FROM barangays
         LEFT JOIN barangay_details ON barangay_details.barangay_id = barangays.barangay_id'
    );

    if (!$result) {
        throw new RuntimeException('Unable to load existing barangay hazard-map data: ' . $connection->error);
    }

    $polygons = [];
    $points = [];
    $latitudes = [];
    $longitudes = [];
    $riskLevels = [];
    $updatedAt = null;

    while ($row = $result->fetch_assoc()) {
        $rowPolygons = decodeMunicipalHazardMapFeatures($row['hazard_polygons'] ?? null, 'barangay hazard polygons');
        $rowPoints = decodeMunicipalHazardMapFeatures($row['hazard_points'] ?? null, 'barangay hazard points');
        foreach ($rowPolygons as $polygon) {
            $polygon['barangay_name'] = $row['barangay_name'];
            $polygons[] = $polygon;
        }
        foreach ($rowPoints as $point) {
            $point['barangay_name'] = $row['barangay_name'];
            $points[] = $point;
        }

        if (is_numeric($row['map_lat']) && is_numeric($row['map_lng'])) {
            $latitudes[] = (float) $row['map_lat'];
            $longitudes[] = (float) $row['map_lng'];
        }
        if (in_array($row['risk_level'], ['Low', 'Medium', 'High', 'Critical'], true)) {
            $riskLevels[] = $row['risk_level'];
        }
        if ($row['hazard_map_updated_at'] && (!$updatedAt || $row['hazard_map_updated_at'] > $updatedAt)) {
            $updatedAt = $row['hazard_map_updated_at'];
        }
    }
    $result->free();

    $riskLevel = 'Low';
    foreach (['Critical', 'High', 'Medium', 'Low'] as $candidate) {
        if (in_array($candidate, $riskLevels, true)) {
            $riskLevel = $candidate;
            break;
        }
    }

    return [
        'lat' => $latitudes ? array_sum($latitudes) / count($latitudes) : 13.3597,
        'lng' => $longitudes ? array_sum($longitudes) / count($longitudes) : 123.7311,
        'zoom' => 7,
        'risk_level' => $riskLevel,
        'polygons' => $polygons,
        'points' => $points,
        'updated_at' => $updatedAt,
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
