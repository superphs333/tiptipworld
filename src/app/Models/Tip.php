<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Tag;
use Carbon\Carbon;
use App\Services\FileStorageService;

class Tip extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'thumbnail',
        'content',
        'excerpt',
        'status',
        'visibility',
        'published_at',
        'tags_count',
        'view_count',
        'like_count',
    ];

    protected $appends = [
        'thumbnailUrl'
    ];

    /**
     * Tip - Category (N:1)
     * tips.category_id -> caregories.id
     */
    public function category(){
        return $this->belongsTo(Category::class);
    }

    /**
     * Tip - Tag (M:N)
     * pivot : tip_tag (tip_id, tag_id)
     */
    public function tags() : BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tip_tag','tip_id', 'tag_id')->withTimestamps();
    }

    /**
     * Tip - User 
     */
    public function user(){
        return $this->belongsTo(User::class);
    }


    /**
     * 썸네일 이미지 접근자
     */
    public function getThumbnailUrlAttribute() : string
    {
        if(!$this->thumbnail){
            return asset('images/no-thumbnail.png');
        }
        return app(FileStorageService::class)->url($this->thumbnail);
    }



    /**
     * Tip 목록 가져오기
     * 
     */
    public static function getTips(array $filters = [], int $perPage = 20){
        $q = Tip::query()
            ->with('category')
            ->with('tags:id,name')
            ->with('user');

        // 쿼리(검색어) : title or user
        if(isset($filters['query'])){
            $keyword = trim((string) $filters['query']);
            if($keyword !== ''){
                $q->where(function ($searchQ) use ($keyword) {
                    $searchQ->where('title','like',"%{$keyword}%")
                        ->orWhereHas('user', function ($uq) use ($keyword){
                            $uq->where('name','like',"%{$keyword}%");
                        });
                });
            }
        }

        // 카테고리
        $categoryFilter = $filters['category_id'] ?? null;
        if($categoryFilter !== null){
            $categoryFilter = trim((string) $categoryFilter);

            if($categoryFilter === 'uncategorized'){
                $q->whereNull('category_id');
            }elseif($categoryFilter !== '' && $categoryFilter !== 'all'){
                $q->where('category_id', $categoryFilter);
            }
        }

        // 상태
        if(array_key_exists('status', $filters)){
            $status = trim((string) $filters['status']);
            if($status !== ''){
                $q->where('status', $status);
            }
        }

        // 노출여부
        if(array_key_exists('visibility', $filters)){
            $visibility = trim((string) $filters['visibility']);
            if($visibility !== ''){
                $q->where('visibility', $visibility);
            }
        }


        // 기간
        if(isset($filters['start_date']) || isset($filters['end_date'])){
            $startAt = filled($filters['start_date'] ?? null) ? Carbon::parse($filters['start_date']) : null;
            $endAt   = filled($filters['end_date'] ?? null)   ? Carbon::parse($filters['end_date'])   : null;     
            
            // 시작 날짜가 종료 날자보다 마래일 경우, 두 날짜의 값을 서로 맞밖ㅁ
            if ($startAt && $endAt && $startAt->gt($endAt)) {
                [$startAt, $endAt] = [$endAt, $startAt];
            }

            // 쿼리
            $q->when($startAt, fn ($q) => $q->where('created_at', '>=', $startAt))
                ->when($endAt, fn ($q) => $q->where('created_at', '<=', $endAt));
        }

        

        // 정렬

        
        // 결과
        return $q->orderBy('id')->paginate($perPage)->withQueryString();


    }
}
