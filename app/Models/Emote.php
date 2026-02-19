<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emote extends Model
{
    protected $fillable = [
        'emote_id',
        'guild_id',
        'emote_name',
        'type',
    ];

    protected $casts = [
        'emote_id' => 'string',
        'guild_id' => 'string',
    ];

    public function logs()
    {
        return $this->hasMany(
            EmoteLog::class,
            'emote_id',
            'emote_id'
        );
    }

    public function guildStats()
    {
        return $this->hasMany(
            EmoteGuildStat::class,
            'emote_id',
            'emote_id'
        );
    }

    public function scopeForGuild($query, $guildId)
    {
        return $query->where('guild_id', $guildId);
    }

    public function addReaction(User $user, string $guildId, string $channelId, string $messageId, ?string $unicodeEmoji = null)
    {
        $exists = EmoteLog::where([
            'emote_id' => $this->emote_id,
            'user_id' => $user->discord_id,
            'guild_id' => $guildId,
            'message_id' => $messageId,
            'emoji_unicode' => boolval($unicodeEmoji),
        ])->exists();

        if ($exists) {
            return;
        }

        EmoteLog::create([
            'emote_id' => $this->emote_id,
            'user_id' => $user->discord_id,
            'guild_id' => $guildId,
            'channel_id' => $channelId,
            'message_id' => $messageId,
            'emoji_unicode' => boolval($unicodeEmoji),
        ]);

        if ($unicodeEmoji === null) {
            EmoteGuildStat::firstOrCreate(
                ['emote_id' => $this->emote_id, 'guild_id' => $guildId],
                ['usage_count' => 0]
            )->increment('usage_count');
        }

        UserGuildStat::firstOrCreate(
            [
                'user_id' => $user->discord_id,
                'guild_id' => $guildId,
                'emote_id' => $this->emote_id,
            ],
            [
                'usage_count' => 0,
                'emote_id' => $this->emote_id,
            ]
        )->increment('usage_count');
    }

    public function removeReaction(User $user, string $guildId, string $messageId, ?string $unicodeEmoji = null)
    {
        $deleted = EmoteLog::where([
            'emote_id' => $this->emote_id,
            'user_id' => $user->discord_id,
            'guild_id' => $guildId,
            'message_id' => $messageId,
            'emoji_unicode' => boolval($unicodeEmoji),
        ])->delete();

        if ($deleted) {

            if ($unicodeEmoji === null) {
                EmoteGuildStat::where(['emote_id' => $this->emote_id, 'guild_id' => $guildId])
                    ->decrement('usage_count');
            }

            UserGuildStat::where(['user_id' => $user->discord_id, 'guild_id' => $guildId])
                ->decrement('usage_count');
        }
    }
}
