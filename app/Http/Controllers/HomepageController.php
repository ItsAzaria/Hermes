<?php

namespace App\Http\Controllers;

use App\Models\EmoteGuildStat;
use App\Models\EmoteLog;
use App\Models\UserGuildStat;
use Illuminate\Http\Request;

class HomepageController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $rawUserIdFilter = trim((string) $request->query('user_id', ''));
        $userIdFilter = ctype_digit($rawUserIdFilter) ? $rawUserIdFilter : '';
        $isFiltered = $userIdFilter !== '';

        $validationErrors = [];

        if ($rawUserIdFilter !== '' && ! ctype_digit($rawUserIdFilter)) {
            $validationErrors['user_id'] = 'User ID must contain only numbers.';
        }

        $stats = $this->buildStats($userIdFilter, $isFiltered);

        return view('components.homepage', [
            'stats' => $stats,
            'userIdInput' => $rawUserIdFilter,
            'validationErrors' => $validationErrors,
        ]);
    }

    /**
     * Build homepage stats.
     */
    protected function buildStats(string $userIdFilter, bool $isFiltered): array
    {
        $resolvedUserId = $isFiltered ? $userIdFilter : null;

        if ($isFiltered) {
            $aggregate = UserGuildStat::dashboardAggregateForUser($userIdFilter);
        } else {
            $aggregate = EmoteGuildStat::dashboardAggregate();
        }

        $usageOverTime = EmoteLog::usageOverTime($resolvedUserId, 30);
        $topMovers = EmoteLog::topMovers($resolvedUserId, 7, 10);
        $uniqueUsers = $isFiltered ? (int) ($aggregate['unique_users'] ?? 0) : EmoteLog::uniqueUsersCount();

        return [
            'is_filtered' => $isFiltered,
            'filtered_user_id' => $isFiltered ? $userIdFilter : null,
            'total_usage' => (int) ($aggregate['total_usage'] ?? 0),
            'unique_emotes' => (int) ($aggregate['unique_emotes'] ?? 0),
            'unique_users' => $uniqueUsers,
            'usage_by_type' => $aggregate['usage_by_type'] ?? ['STATIC' => 0, 'ANIMATED' => 0, 'UNICODE' => 0],
            'usage_over_time' => $usageOverTime,
            'top_movers' => $topMovers,
            'top_static' => $aggregate['top_static'] ?? collect(),
            'top_animated' => $aggregate['top_animated'] ?? collect(),
            'top_unicode' => $aggregate['top_unicode'] ?? collect(),
        ];
    }
}
