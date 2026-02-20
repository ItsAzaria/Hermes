<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Build aggregate stats for all users.
     */
    public static function dashboardAggregate(): array
    {
        $baseQuery = self::query()
            ->join('emotes', 'emote_guild_stats.emote_id', '=', 'emotes.emote_id');

        $summary = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(emote_guild_stats.usage_count), 0) as total_usage')
            ->selectRaw('COUNT(DISTINCT emote_guild_stats.emote_id) as unique_emotes')
            ->first();

        $usageByType = (clone $baseQuery)
            ->select('emotes.type')
            ->selectRaw('COALESCE(SUM(emote_guild_stats.usage_count), 0) as total_usage')
            ->groupBy('emotes.type')
            ->pluck('total_usage', 'type');

        $unicodeUsage = (int) EmoteLog::query()
            ->join('emotes', 'emote_logs.emote_id', '=', 'emotes.emote_id')
            ->where('emotes.type', 'UNICODE')
            ->count();

        $unicodeUniqueEmotes = (int) EmoteLog::query()
            ->join('emotes', 'emote_logs.emote_id', '=', 'emotes.emote_id')
            ->where('emotes.type', 'UNICODE')
            ->distinct('emote_logs.emote_id')
            ->count('emote_logs.emote_id');

        $topUnicode = EmoteLog::query()
            ->join('emotes', 'emote_logs.emote_id', '=', 'emotes.emote_id')
            ->where('emotes.type', 'UNICODE')
            ->select(
                'emote_logs.emote_id',
                'emotes.emote_name',
                'emotes.type',
                'emotes.image'
            )
            ->selectRaw('COUNT(*) as total_usage')
            ->groupBy(
                'emote_logs.emote_id',
                'emotes.emote_name',
                'emotes.type',
                'emotes.image'
            )
            ->orderByDesc('total_usage')
            ->limit(10)
            ->get();

        $baseTotalUsage = (int) ($summary->total_usage ?? 0);
        $baseUniqueEmotes = (int) ($summary->unique_emotes ?? 0);

        return [
            'total_usage' => $baseTotalUsage + $unicodeUsage,
            'unique_emotes' => $baseUniqueEmotes + $unicodeUniqueEmotes,
            'usage_by_type' => [
                'STATIC' => (int) ($usageByType->get('STATIC') ?? 0),
                'ANIMATED' => (int) ($usageByType->get('ANIMATED') ?? 0),
                'UNICODE' => $unicodeUsage,
            ],
            'top_static' => self::topEmotesByType(
                $baseQuery,
                'STATIC',
                'emote_guild_stats.usage_count',
                'emote_guild_stats.emote_id'
            ),
            'top_animated' => self::topEmotesByType(
                $baseQuery,
                'ANIMATED',
                'emote_guild_stats.usage_count',
                'emote_guild_stats.emote_id'
            ),
            'top_unicode' => $topUnicode,
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
