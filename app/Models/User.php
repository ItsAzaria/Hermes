<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'username',
        'discord_id',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'discord_id' => 'string',
    ];

    public function emoteLogs()
    {
        return $this->hasMany(EmoteLog::class, 'user_id', 'discord_id');
    }

    public function emotes()
    {
        return $this->hasManyThrough(
            Emote::class,
            EmoteLog::class,
            'user_id',
            'emote_id',
            'discord_id',
            'emote_id'
        );
    }

    public function hasUsedEmote($emoteId): bool
    {
        return $this->emoteLogs()
            ->where('emote_id', $emoteId)
            ->exists();
    }
}
