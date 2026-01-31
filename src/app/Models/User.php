<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
        // 필요하면 여기에 profile_image_path 같은 컬럼도 추가
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

        return Storage::disk('r2')->url($this->profile_image_path);
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
}
