<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchKeywordService
{
    public function record(Request $request, string $rawKeyword): void
    {
        $keyword = Str::lower(Str::squish($rawKeyword));

        if ($keyword === '') {
            return;
        }

        if (max((int) $request->query('page', 1), 1) > 1) {
            return;
        }

        $viewerKey = Auth::check()
            ? 'u_' . Auth::id()
            : 's_' . $request->session()->getId();

        $dedupeKey = 'tips:search:dedupe:' . $viewerKey . ':' . sha1($keyword);
        $rankingKey = 'tips:search:popular:daily:' . now()->format('Y-m-d');

        try {
            $isFirst = Cache::store('redis')->add(
                $dedupeKey,
                1,
                now()->addMinutes(5)
            );

            if (!$isFirst) {
                return;
            }

            Redis::connection('cache')->zIncrBy($rankingKey, 1, $keyword);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function top(int $limit = 5): array
    {
        $rankingKey = 'tips:search:popular:daily:' . now()->format('Y-m-d');

        try {
            $rows = Redis::connection('cache')->zRevRange($rankingKey, 0, $limit - 1, true);
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }

        $result = [];
        $rank = 1;

        foreach ($rows as $keyword => $count) {
            $result[] = [
                'rank' => $rank++,
                'keyword' => (string) $keyword,
                'count' => (int) $count,
            ];
        }

        return $result;
    }
}
