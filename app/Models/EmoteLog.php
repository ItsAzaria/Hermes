<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmoteLog extends Model
{
    protected $fillable = [
        'emote_id',
        'user_id',
        'guild_id',
        'channel_id',
        'message_id',
        'emoji_unicode',
    ];

    protected $casts = [
        'emote_id' => 'string',
        'guild_id' => 'string',
        'channel_id' => 'string',
        'message_id' => 'string',
        'emoji_unicode' => 'boolean',
    ];

    public function emote()
    {
        return $this->belongsTo(
            Emote::class,
            'emote_id',
            'emote_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'discord_id');
    }
}
