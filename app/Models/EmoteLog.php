<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    /**
     * Count distinct users for logs.
     */
    public static function uniqueUsersCount(?string $userId = null): int
    {
        return (int) self::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Build usage-over-time dataset.
     */
    public static function usageOverTime(?string $userId, int $days = 30): array
    {
        $today = now();
        $startDate = $today->copy()->subDays($days - 1)->startOfDay();

        $rows = self::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as usage_date')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->pluck('usage_count', 'usage_date');

        $dateRange = collect(range($days - 1, 0))->map(fn (int $offset) => $today->copy()->subDays($offset));

        return [
            'labels' => $dateRange->map(fn ($date) => $date->format('m/d'))->values(),
            'values' => $dateRange->map(
                fn ($date) => (int) ($rows->get($date->format('Y-m-d')) ?? 0)
            )->values(),
        ];
    }

    /**
     * Build stacked daily trend data by emote type.
     */
    public static function stackedDailyTrend(?string $userId, int $days = 30): array
    {
        $today = now();
        $startDate = $today->copy()->subDays($days - 1)->startOfDay();

        $rows = self::query()
            ->join('emotes', 'emote_logs.emote_id', '=', 'emotes.emote_id')
            ->when($userId !== null, fn ($query) => $query->where('emote_logs.user_id', $userId))
            ->where('emote_logs.created_at', '>=', $startDate)
            ->selectRaw('DATE(emote_logs.created_at) as usage_date')
            ->select('emotes.type')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('usage_date', 'emotes.type')
            ->get();

        $indexed = $rows->mapWithKeys(function ($row) {
            return [$row->usage_date.'|'.$row->type => (int) $row->usage_count];
        });

        $dateRange = collect(range($days - 1, 0))->map(fn (int $offset) => $today->copy()->subDays($offset));
        $labels = $dateRange->map(fn ($date) => $date->format('m/d'))->values();

        $valuesForType = function (string $type) use ($dateRange, $indexed) {
            return $dateRange->map(function ($date) use ($type, $indexed) {
                $key = $date->format('Y-m-d').'|'.$type;

                return (int) ($indexed->get($key) ?? 0);
            })->values();
        };

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Static', 'values' => $valuesForType('STATIC')],
                ['label' => 'Animated', 'values' => $valuesForType('ANIMATED')],
                ['label' => 'Unicode', 'values' => $valuesForType('UNICODE')],
            ],
        ];
    }

    /**
     * Build day/hour heatmap data.
     */
    public static function heatmap(?string $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $logs = self::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->where('created_at', '>=', $startDate)
            ->get(['created_at']);

        $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $hours = range(0, 23);
        $matrix = [];

        foreach ($daysOfWeek as $day) {
            $matrix[$day] = array_fill(0, 24, 0);
        }

        foreach ($logs as $log) {
            $timestamp = Carbon::parse($log->created_at);
            $day = $daysOfWeek[$timestamp->dayOfWeek];
            $hour = $timestamp->hour;
            $matrix[$day][$hour]++;
        }

        $max = collect($matrix)->flatten()->max() ?: 0;

        return [
            'days' => $daysOfWeek,
            'hours' => $hours,
            'matrix' => $matrix,
            'max' => $max,
        ];
    }

    /**
     * Build top movers from current vs previous window.
     */
    public static function topMovers(?string $userId, int $windowDays = 7, int $limit = 10): Collection
    {
        $now = now();
        $currentStart = $now->copy()->subDays($windowDays - 1)->startOfDay();
        $previousStart = $currentStart->copy()->subDays($windowDays);
        $previousEnd = $currentStart->copy()->subSecond();

        $queryForRange = function (Carbon $start, Carbon $end) use ($userId) {
            return self::query()
                ->join('emotes', 'emote_logs.emote_id', '=', 'emotes.emote_id')
                ->when($userId !== null, fn ($query) => $query->where('emote_logs.user_id', $userId))
                ->whereBetween('emote_logs.created_at', [$start, $end])
                ->select('emote_logs.emote_id', 'emotes.emote_name', 'emotes.type', 'emotes.image')
                ->selectRaw('COUNT(*) as usage_count')
                ->groupBy('emote_logs.emote_id', 'emotes.emote_name', 'emotes.type', 'emotes.image')
                ->get();
        };

        $currentRows = $queryForRange($currentStart, $now->copy()->endOfDay());
        $previousRows = $queryForRange($previousStart, $previousEnd);

        $metadata = collect();
        $currentById = collect();
        $previousById = collect();

        foreach ($currentRows as $row) {
            $metadata->put($row->emote_id, [
                'emote_id' => $row->emote_id,
                'emote_name' => $row->emote_name,
                'type' => $row->type,
                'image' => $row->image,
            ]);
            $currentById->put($row->emote_id, (int) $row->usage_count);
        }

        foreach ($previousRows as $row) {
            if (! $metadata->has($row->emote_id)) {
                $metadata->put($row->emote_id, [
                    'emote_id' => $row->emote_id,
                    'emote_name' => $row->emote_name,
                    'type' => $row->type,
                    'image' => $row->image,
                ]);
            }

            $previousById->put($row->emote_id, (int) $row->usage_count);
        }

        return $metadata
            ->map(function (array $meta, string $emoteId) use ($currentById, $previousById) {
                $current = (int) ($currentById->get($emoteId) ?? 0);
                $previous = (int) ($previousById->get($emoteId) ?? 0);
                $delta = $current - $previous;

                return (object) array_merge($meta, [
                    'current_count' => $current,
                    'previous_count' => $previous,
                    'delta' => $delta,
                ]);
            })
            ->sortByDesc(fn ($row) => abs($row->delta))
            ->take($limit)
            ->values();
    }
}
