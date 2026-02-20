<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Build aggregate stats for a single user.
     */
    public static function dashboardAggregateForUser(string $userId): array
    {
        $baseQuery = self::query()
            ->join('emotes', 'user_guild_stats.emote_id', '=', 'emotes.emote_id')
            ->where('user_guild_stats.user_id', $userId);

        $summary = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(user_guild_stats.usage_count), 0) as total_usage')
            ->selectRaw('COUNT(DISTINCT user_guild_stats.emote_id) as unique_emotes')
            ->selectRaw('COUNT(DISTINCT user_guild_stats.user_id) as unique_users')
            ->first();

        $usageByType = (clone $baseQuery)
            ->select('emotes.type')
            ->selectRaw('COALESCE(SUM(user_guild_stats.usage_count), 0) as total_usage')
            ->groupBy('emotes.type')
            ->pluck('total_usage', 'type');

        return [
            'total_usage' => (int) ($summary->total_usage ?? 0),
            'unique_emotes' => (int) ($summary->unique_emotes ?? 0),
            'unique_users' => (int) ($summary->unique_users ?? 0),
            'usage_by_type' => [
                'STATIC' => (int) ($usageByType->get('STATIC') ?? 0),
                'ANIMATED' => (int) ($usageByType->get('ANIMATED') ?? 0),
                'UNICODE' => (int) ($usageByType->get('UNICODE') ?? 0),
            ],
            'top_static' => self::topEmotesByType(
                $baseQuery,
                'STATIC',
                'user_guild_stats.usage_count',
                'user_guild_stats.emote_id'
            ),
            'top_animated' => self::topEmotesByType(
                $baseQuery,
                'ANIMATED',
                'user_guild_stats.usage_count',
                'user_guild_stats.emote_id'
            ),
            'top_unicode' => self::topEmotesByType(
                $baseQuery,
                'UNICODE',
                'user_guild_stats.usage_count',
                'user_guild_stats.emote_id'
            ),
        ];
    }

    /**
     * Build a top-10 emote list by type.
     */
    protected static function topEmotesByType(
        $baseQuery,
        string $type,
        string $usageCountColumn,
        string $emoteIdColumn
    ): Collection {
        return (clone $baseQuery)
            ->where('emotes.type', $type)
            ->select(
                $emoteIdColumn.' as emote_id',
                'emotes.emote_name',
                'emotes.type',
                'emotes.image'
            )
            ->selectRaw("COALESCE(SUM({$usageCountColumn}), 0) as total_usage")
            ->groupBy(
                $emoteIdColumn,
                'emotes.emote_name',
                'emotes.type',
                'emotes.image'
            )
            ->orderByDesc('total_usage')
            ->limit(10)
            ->get();
    }
}
