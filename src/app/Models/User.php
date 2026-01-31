<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

    public function getProfileImageUrlAttribute(): string
    {
        if (! $this->profile_image_path) {
            return asset('images/avatar-default.svg');
        }

        return Storage::disk('r2')->url($this->profile_image_path);
    }

    public function roles() : BelongsToMany{
        return $this->belongToMany(Role::class, 'role_user')->withTimestamps();
    }

    /*
    Member 역할 고정
    */
    // role이 하나도 없으면 member
    public function isMember() : bool{
        return !$this->roles()->exists();
    }
    public function hasRole(String $key):bool{
        return $this->roles()->where('key',$key)->exists();
    }
    public function isAdmin() : bool{
        return $this->hasRole('admin');
    }
    public function isEditor() :bool{
        return $this->hasRole('editor');
    }

    /*
    부여/회수 메서드
    */
    public function assignRole(string $key) : void {
        $roleId = Role::where('key',$key)->value('id');
        if($roleId){
            $this->roles()->syncWithoutDetaching([$roleId]);        
        }
    }
    public function removeRole(string $key): void
    {
        $roleId = \App\Models\Role::where('key', $key)->value('id');
        if ($roleId) {
            $this->roles()->detach($roleId);
        }
    }


    
}
