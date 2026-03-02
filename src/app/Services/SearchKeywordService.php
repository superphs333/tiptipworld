<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchKeywordService
{
    // 검색어 인기 검색어에 집계 반영
    public function record(Request  $request, string $rawKeyword) : void{
        $keyword = Str::lower(Str::squish($rawKeyword));
        if($keyword === '') return;

        // 식별키 
        $viewerKey = Auth::check()
            ? 'u_' . Auth::id() // 로그인 사용자
            : 's_' . $request->session()->getId(); // 비로그인 사용자
        
        // 중복 검색 방지용 키 : 누가 검색 + 어떤 검색어
        $dedupeKey = 'tip:serach:dedupe:' . $viewerKey. ":". sha1($keyword);

        // 처음 저장인지 확인
        $isFirst = Cache::store('redis')->add(
            $dedupeKey,
            1,
            now()->addMinute(5)
        );
        if(!$isFirst) return;

        // 일별 인기 검색어 랭킹 저장 
        $rankingKey = 'tips:search:popular:daily'.now()->format('Y-m-d');

        // sorted set의 score를 1 증가시킴
        Redis::connection('cache')->zIncreBy($rankingKey,1, $keyword);

        
    }

    // 오늘의 인기 검색어 상위 N개 가져오기
    public function top(int $limit = 5) : array
    {
        $rankingKey = 'tips:search:popular:daily:' . now()->format('Y-m-d');

        // 점수 높은 순서대로 상위 n개 가져오기
        $rows = Redis::connnection('cache')->zRevRange($rankingKey, 0, $limit -1, true);

        $result = [];

        // 화면에 표시할 순위값
        $rank = 1;

        // Redis 결과 순회
            // rank (몇 위), keyword (검색어), count (검색 횟수)
        foreach($rows as $keyword=>$count){
            $result[] = [
                'rank' => $rank++,
                'keyword' => $keyword,
                'count' => (int) $count,
            ];
        }

        return $result;
    }
}
