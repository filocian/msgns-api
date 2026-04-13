<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserGoogleBusinessConnection extends Model
{
    protected $table = 'user_google_business_connections';

    protected $fillable = [
        'user_id',
        'google_account_id',
        'business_location_id',
        'business_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token'     => 'encrypted',
            'refresh_token'    => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns true if the token is expired or will expire within 5 minutes.
     *
     * Boundary: token expiring at now() + 4m59s → true (expired).
     * Token expiring at now() + 5m01s → false (still valid).
     * Null token_expires_at → false (treat as valid; let API fail and handle at that level).
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->subMinutes(5)->isPast();
    }
}
