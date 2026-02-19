<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGuildStat extends Model
{
    protected $fillable = [
        'user_id',
        'guild_id',
        'emote_id',
        'usage_count',
    ];

    protected $casts = [
        'user_id' => 'string',
        'guild_id' => 'string',
        'emote_id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'discord_id');
    }
}
