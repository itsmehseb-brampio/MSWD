<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id('announcement_id');
            $table->unsignedBigInteger('admin_id');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_pinned')->default(0);
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });

        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedBigInteger('barangay_id');
            $table->foreign('announcement_id')->references('announcement_id')->on('announcements')->cascadeOnDelete();
            $table->foreign('barangay_id')->references('barangay_id')->on('barangays')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
        Schema::dropIfExists('announcements');
    }
};
