<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emotes', function (Blueprint $table) {
            $table->id();

            $table->string('emote_id')->unique();
            $table->unsignedBigInteger('guild_id')->index();

            $table->string('emote_name');
            $table->enum('type', ['STATIC', 'ANIMATED', 'UNICODE']);

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emotes');
    }
};
