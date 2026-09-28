<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('invite_token', 64)->nullable()->unique()->after('password');
            $table->timestamp('invite_expires_at')->nullable()->after('invite_token');
            $table->string('username')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['invite_token']);
            $table->dropColumn(['email', 'invite_token', 'invite_expires_at']);
            $table->string('username')->change();
        });
    }
};