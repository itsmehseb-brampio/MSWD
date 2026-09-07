<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relief_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('distribution_date');
            $table->time('distribution_time')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('upcoming');
            $table->timestamps();
        });

        Schema::create('relief_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('schedule_id');
            $table->string('item_name');
            $table->integer('quantity')->default(0);
            $table->string('unit')->nullable();
            $table->foreign('schedule_id')->references('schedule_id')->on('relief_schedules')->cascadeOnDelete();
        });

        Schema::create('relief_schedule_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('barangay_id');
            $table->foreign('schedule_id')->references('schedule_id')->on('relief_schedules')->cascadeOnDelete();
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
        });

        Schema::create('relief_distribution_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('barangay_id');
            $table->string('confirmed_by')->nullable();
            $table->text('narrative')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->foreign('schedule_id')->references('schedule_id')->on('relief_schedules')->cascadeOnDelete();
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
        });

        Schema::create('relief_distribution_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('report_id');
            $table->string('file_path');
            $table->foreign('report_id')->references('report_id')->on('relief_distribution_reports')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relief_distribution_documents');
        Schema::dropIfExists('relief_distribution_reports');
        Schema::dropIfExists('relief_schedule_targets');
        Schema::dropIfExists('relief_items');
        Schema::dropIfExists('relief_schedules');
    }
};
