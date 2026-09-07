<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_format_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_label');
            $table->string('field_name');
            $table->string('field_type')->default('text');
            $table->text('field_options')->nullable();
            $table->boolean('is_required')->default(0);
            $table->integer('field_order')->default(0);
            $table->timestamps();
        });

        Schema::create('disaster_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('barangay_id');
            $table->string('format_no')->nullable();
            $table->string('title')->nullable();
            $table->string('disaster_type')->nullable();
            $table->string('household_head')->nullable();
            $table->integer('family_members')->nullable();
            $table->text('full_address')->nullable();
            $table->string('housing_type')->nullable();
            $table->string('damage_extent')->nullable();
            $table->text('description')->nullable();
            $table->string('captain_name')->nullable();
            $table->string('secretary_name')->nullable();
            $table->string('pic1')->nullable();
            $table->string('pic2')->nullable();
            $table->string('pic3')->nullable();
            $table->string('pic4')->nullable();
            $table->string('b2b_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('decline_reason')->nullable();
            $table->timestamps();
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_reports');
        Schema::dropIfExists('disaster_format_fields');
    }
};
