<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Products\Domain\Entities\WhatsappPhone;

/**
 * @property int $id
 * @property int $product_id
 * @property string $phone
 * @property string $prefix
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EloquentWhatsappPhone extends Model
{
    protected $table = 'whatsapp_phones';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'phone',
        'prefix',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EloquentWhatsappMessage::class, 'phone_id');
    }

    public function toDomainEntity(): WhatsappPhone
    {
        return WhatsappPhone::fromPersistence(
            id: $this->id,
            productId: $this->product_id,
            phone: $this->phone,
            prefix: $this->prefix,
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
        );
    }
}
