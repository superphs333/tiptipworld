<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\FileStorageService;
use App\Models\Role;
use App\Models\Tip;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Accessors to append for array / JSON serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'profile_image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 프로필 이미지 URL 접근자
     */
    public function getProfileImageUrlAttribute(): string
    {
        if (!$this->profile_image_path) {
            return asset('images/avatar-default.svg');
        }

        return app(FileStorageService::class)->url($this->profile_image_path);
    }

    /**
     * User ↔ Role (pivot: role_user)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * role이 하나도 없으면 member로 간주
     */
    public function isMember(): bool
    {
        return !$this->roles()->exists();
    }

    /**
     * 특정 role key 보유 여부
     */
    public function hasRole(string $key): bool
    {
        // exists()는 boolean만 빠르게 확인 (roles를 통째로 로드하지 않음)
        return $this->roles()->where('key', $key)->exists();
    }

    /**
     * 관리자 여부
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * 에디터 여부
     */
    public function isEditor(): bool
    {
        return $this->hasRole('editor');
    }

    /**
     * 모더레이터 여부
     */
    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    /**
     * Role 부여 (중복 없이)
     */
    public function assignRole(string $key): void
    {
        $roleId = Role::query()->where('key', $key)->value('id');

        if (!$roleId) {
            return;
        }

        // 기존 role 유지 + 없는 것만 추가
        $this->roles()->syncWithoutDetaching([$roleId]);
    }

    /**
     * Role 회수
     */
    public function removeRole(string $key): void
    {
        $roleId = Role::query()->where('key', $key)->value('id');

        if (!$roleId) {
            return;
        }

        $this->roles()->detach($roleId);
    }

    /**
     * 여러 role을 한 번에 부여 (중복 없이)
     *
     * @param array<int, string> $keys 예: ['admin', 'editor']
     */
    public function assignRoles(array $keys): void
    {
        $roleIds = Role::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        if (empty($roleIds)) {
            return;
        }

        $this->roles()->syncWithoutDetaching($roleIds);
    }

    /**
     * 여러 role을 한 번에 회수
     *
     * @param array<int, string> $keys 예: ['editor', 'moderator']
     */
    public function removeRoles(array $keys): void
    {
        $roleIds = Role::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        if (empty($roleIds)) {
            return;
        }

        $this->roles()->detach($roleIds);
    }


    /**
     * User 검색
     */
    public static function getUsers(array $filters = [], int $perPage = 20){
        $q = User::query()->with('roles');

        if(!empty($filters['provider'])){
            $q->where('provider', $filters['provider']);
        }
        if(!empty($filters['status'])){
            $q->where('status',$filters['status']);
        }
        if(!empty($filters['query'])){
            $keyword = trim($filters['query']);
            $q->where(function ($q2) use($keyword){
                $q2->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');            
            });
        }
        if(!empty($filters['role'])){
            $q->whereHas('roles', fn ($r) => $r->where('key',$filters['role']));
        }

        return $q->orderBy('id')->paginate($perPage)->withQueryString();
    }


    /**
     * 좋아요 관련
     */
    // 관계 (이 유저가 좋아요 한 팁들)
    public function likedTips(): BelongsToMany
    {
        return $this->belongsToMany(
            Tip::class,
            'tip_likes',
            'user_id',
            'tip_id',        
        );
    }
}
