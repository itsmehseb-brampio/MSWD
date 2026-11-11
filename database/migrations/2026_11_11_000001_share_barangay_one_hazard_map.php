<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('municipal_hazard_maps') || !Schema::hasTable('barangay_details')) {
            throw new RuntimeException('Hazard-map tables must exist before sharing the Barangay I map.');
        }

        DB::transaction(function (): void {
            $source = DB::table('barangay_details')
                ->where('barangay_id', 1)
                ->first([
                    'map_lat',
                    'map_lng',
                    'map_zoom',
                    'risk_level',
                    'hazard_polygons',
                    'hazard_points',
                    'hazard_map_updated_at',
                ]);

            if (!$source) {
                throw new RuntimeException('Barangay I map data is unavailable; no maps were changed.');
            }

            DB::table('municipal_hazard_maps')->updateOrInsert(
                ['id' => 1],
                [
                    'map_lat' => is_numeric($source->map_lat) ? $source->map_lat : null,
                    'map_lng' => is_numeric($source->map_lng) ? $source->map_lng : null,
                    'map_zoom' => is_numeric($source->map_zoom) ? max(1, min(20, (int) $source->map_zoom)) : 14,
                    'risk_level' => in_array($source->risk_level, ['Low', 'Medium', 'High', 'Critical'], true)
                        ? $source->risk_level
                        : 'Low',
                    'hazard_polygons' => $source->hazard_polygons,
                    'hazard_points' => $source->hazard_points,
                    'hazard_map_updated_at' => $source->hazard_map_updated_at,
                ]
            );

            DB::table('barangay_details')->update([
                'hazard_map' => null,
                'map_lat' => null,
                'map_lng' => null,
                'map_zoom' => 14,
                'hazard_polygons' => null,
                'hazard_points' => null,
                'hazard_map_updated_at' => null,
            ]);

            if (Schema::hasTable('permissions') && Schema::hasTable('model_has_permissions')) {
                $editPermissionIds = DB::table('permissions')
                    ->where('name', 'edit_hazard_map')
                    ->pluck('id');

                if ($editPermissionIds->isNotEmpty()) {
                    DB::table('model_has_permissions')
                        ->where('model_type', 'App\\Models\\Barangay')
                        ->whereIn('permission_id', $editPermissionIds)
                        ->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // The per-barangay map records are intentionally not recreated on rollback.
    }
};
