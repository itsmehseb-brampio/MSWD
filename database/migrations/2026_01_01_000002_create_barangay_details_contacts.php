<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangay_details', function (Blueprint $table) {
            $table->id('barangay_id');
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
            $table->string('logo')->nullable();
            $table->string('barangay_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('captain_name')->nullable();
            $table->text('councilors')->nullable();
            $table->string('secretary_name')->nullable();
            $table->string('treasurer_name')->nullable();
            $table->text('contact_info')->nullable();
            $table->integer('population')->default(0);
            $table->integer('households')->default(0);
            $table->integer('head_of_household')->default(0);
            $table->text('population_breakdown')->nullable();
            $table->text('boundaries')->nullable();
            $table->text('streets')->nullable();
            $table->string('land_area')->nullable();
            $table->text('gps_coordinates')->nullable();
            $table->text('barangay_hall_address')->nullable();
            $table->text('health_center')->nullable();
            $table->text('daycare_schools')->nullable();
            $table->text('community_centers')->nullable();
            $table->text('emergency_services')->nullable();
            $table->text('date_established')->nullable();
            $table->string('website')->nullable();
            $table->text('ordinances')->nullable();
            $table->text('hazard_map')->nullable();
            $table->text('hazard_description')->nullable();
            $table->string('risk_level')->default('Low');
            $table->text('evacuation_routes')->nullable();
            $table->text('affected_areas')->nullable();
            $table->string('map_lat')->nullable();
            $table->string('map_lng')->nullable();
            $table->integer('map_zoom')->default(14);
            $table->longText('hazard_polygons')->nullable();
            $table->longText('hazard_points')->nullable();
            $table->timestamp('hazard_map_updated_at')->nullable();
            $table->timestamp('last_updated')->nullable();
        });

        Schema::create('barangay_contacts', function (Blueprint $table) {
            $table->id('barangay_id');
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
            $table->string('barangay_hall_phone')->nullable();
            $table->string('barangay_chairman_phone')->nullable();
            $table->string('barangay_secretary_phone')->nullable();
            $table->string('barangay_tanod_phone')->nullable();
            $table->string('police_hotline')->nullable();
            $table->string('fire_hotline')->nullable();
            $table->string('ngo_relief')->nullable();
            $table->string('fire_volunteers')->nullable();
            $table->string('covid_hotline')->nullable();
            $table->timestamp('last_updated')->nullable();
        });

        Schema::create('barangay_edit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barangay_id');
            $table->string('field_name');
            $table->string('field_label')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamp('edited_at');
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
        });

        Schema::create('municipal_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('city_hotline')->nullable();
            $table->string('drrmo_hotline')->nullable();
            $table->string('police_hotline')->nullable();
            $table->string('fire_hotline')->nullable();
            $table->string('medical_services')->nullable();
            $table->string('hospital_emergency')->nullable();
            $table->string('traffic_control')->nullable();
            $table->string('power_emergency')->nullable();
            $table->string('water_emergency')->nullable();
            $table->string('ngo_relief')->nullable();
            $table->string('fire_volunteers')->nullable();
            $table->string('covid_hotline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_contacts');
        Schema::dropIfExists('barangay_edit_log');
        Schema::dropIfExists('barangay_contacts');
        Schema::dropIfExists('barangay_details');
    }
};
