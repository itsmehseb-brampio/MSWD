<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipal_hazard_maps', function (Blueprint $table) {
            $table->id();
            $table->decimal('map_lat', 10, 7)->nullable();
            $table->decimal('map_lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('map_zoom')->default(7);
            $table->string('risk_level')->default('Low');
            $table->longText('hazard_polygons')->nullable();
            $table->longText('hazard_points')->nullable();
            $table->timestamp('hazard_map_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_hazard_maps');
    }
};
