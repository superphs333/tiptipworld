<?php

namespace App\Services;

use App\Models\Tip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;


class HomeViewService
{
    /**
     * 가중치를 계산해서 최근 인기글 가져오기
     *
     * engagement = log(1+views) * 1 + likes * 3 + comments * 5 + bookmarks * 8
     */
    public static function getpopularList(int $limit = 10, int $days = 7): Collection
    {
        $limit = max(1, min($limit, 50));
        $days = max(1, min($days, 30));

        $driver = DB::connection()->getDriverName();
        $logExpr = in_array($driver, ['pgsql', 'sqlite'], true)
            ? 'LN(1 + tips.view_count)'
            : 'LOG(1 + tips.view_count)';

        $result = Tip::query()
            ->where('tips.status', 'published')
            ->where('tips.visibility', 'public')
            ->where('tips.created_at', '>=', now()->subDays($days))
            ->select('tips.*')
            ->selectRaw("
                ({$logExpr} * 1)
                + (tips.like_count * 3)
                + (tips.comment_count * 5)
                + (tips.bookmark_count * 8)
                as engagement
            ")
            ->with([
                'user:id,name,profile_image_path',
                'category:id,name',
            ])
            ->orderByDesc('engagement')
            ->orderByDesc('tips.id')
            ->limit($limit)
            ->get();

        return $result;
    }
}
