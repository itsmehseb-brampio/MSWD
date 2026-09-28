<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('barangay_name');
            $table->unique('barangay_name');
        });
    }

    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['barangay_name']);
            $table->dropColumn('username');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};