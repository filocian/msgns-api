<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Ai\Domain\DataTransferObjects\AiResponseRecord as AiResponseRecordDto;
use Src\Shared\Core\Helpers\UuidGenerator;

/**
 * @property string $id
 * @property int $user_id
 * @property string $product_type
 * @property int $product_id
 * @property string $ai_content
 * @property string|null $edited_content
 * @property string $status
 * @property string $system_prompt_snapshot
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $applied_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
final class AiResponseRecordModel extends Model
{
    protected $table = 'ai_responses';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'product_type',
        'product_id',
        'ai_content',
        'edited_content',
        'status',
        'system_prompt_snapshot',
        'metadata',
        'expires_at',
        'applied_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'applied_at' => 'datetime',
        'metadata'   => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id = UuidGenerator::generate();
            $model->expires_at ??= now()->addDays(5);
        });
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toDto(): AiResponseRecordDto
    {
        /** @var array<string, mixed>|null $metadata */
        $metadata = $this->metadata;

        return new AiResponseRecordDto(
            id: (string) $this->id,
            userId: (int) $this->user_id,
            productType: (string) $this->product_type,
            productId: (int) $this->product_id,
            aiContent: (string) $this->ai_content,
            editedContent: $this->edited_content,
            status: (string) $this->status,
            metadata: $metadata ?? [],
            createdAt: $this->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
        );
    }
}
