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
        Schema::create('emote_logs', function (Blueprint $table) {
            $table->id();

            $table->string('emote_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('guild_id')->index();
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('message_id');
            $table->boolean('emoji_unicode')->default(false);

            $table->timestamps();

            $table->foreign('emote_id')
                ->references('emote_id')
                ->on('emotes')
                ->onDelete('cascade');

            $table->index(['guild_id', 'emote_id']);
            $table->index(['guild_id', 'user_id']);
            $table->index(['guild_id', 'message_id']);

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emote_logs');
    }
};
