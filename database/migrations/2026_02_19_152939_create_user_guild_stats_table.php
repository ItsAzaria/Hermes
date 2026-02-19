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
        Schema::create('user_guild_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('guild_id');
            $table->string('emote_id');
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('emote_id')
                ->references('emote_id')
                ->on('emotes')
                ->onDelete('cascade');

            $table->unique(['user_id', 'guild_id', 'emote_id']);

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_guild_stats');
    }
};
