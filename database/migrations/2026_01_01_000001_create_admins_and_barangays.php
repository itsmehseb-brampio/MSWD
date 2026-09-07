<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id('barangay_id');
            $table->string('barangay_name');
            $table->text('address')->nullable();
            $table->string('password');
            $table->timestamp('last_active')->nullable();
            $table->boolean('disaster_open')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
        Schema::dropIfExists('admins');
    }
};
