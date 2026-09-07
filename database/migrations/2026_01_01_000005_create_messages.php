<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id('gm_id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->string('receiver_type');
            $table->unsignedBigInteger('receiver_id');
            $table->text('message');
            $table->boolean('is_read')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('group_messages');
    }
};
