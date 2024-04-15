<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function metadata()
    {
        return $this->hasMany(UserMetadata::class);
    }

    /**
     * Get specific metadata by key[].
     *
     * @param array $keys
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMetadataByKeys(array $keys): \Illuminate\Database\Eloquent\Collection
    {
        return $this->metadata()->whereIn('key', $keys)->get();
    }
}
