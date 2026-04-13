<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserInstagramConnection extends Model
{
    protected $table = 'user_instagram_connections';

    protected $fillable = [
        'user_id',
        'instagram_user_id',
        'instagram_username',
        'page_id',
        'access_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'expires_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Returns true when the token expires within 7 days.
     * Used by the expiry guard to throw InstagramConnectionExpired early,
     * giving the frontend time to prompt reconnection before the token dies.
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(now()->addDays(7));
    }
}
