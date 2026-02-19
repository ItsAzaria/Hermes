<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmoteGuildStat extends Model
{
    protected $fillable = [
        'emote_id',
        'guild_id',
        'usage_count',
    ];

    protected $casts = [
        'emote_id' => 'string',
        'guild_id' => 'string',
    ];

    public function emote()
    {
        return $this->belongsTo(
            Emote::class,
            'emote_id',
            'emote_id'
        );
    }
}
