<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Products\Domain\Entities\WhatsappMessage;

/**
 * @property int $id
 * @property int $product_id
 * @property int $phone_id
 * @property int $locale_id
 * @property string $message
 * @property bool $default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EloquentWhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'phone_id',
        'locale_id',
        'message',
        'default',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'default' => 'boolean',
    ];

    public function phone(): BelongsTo
    {
        return $this->belongsTo(EloquentWhatsappPhone::class, 'phone_id');
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(EloquentWhatsappLocale::class, 'locale_id');
    }

    public function toDomainEntity(): WhatsappMessage
    {
        $phone = $this->relationLoaded('phone') && $this->phone !== null
            ? $this->phone
            : null;

        $locale = $this->relationLoaded('locale') && $this->locale !== null
            ? $this->locale
            : null;

        return WhatsappMessage::fromPersistence(
            id: $this->id,
            productId: $this->product_id,
            phoneId: $this->phone_id,
            localeId: $this->locale_id,
            localeCode: $locale?->code ?? '',
            message: $this->message,
            isDefault: $this->default,
            phone: $phone?->phone ?? '',
            prefix: $phone?->prefix ?? '',
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
        );
    }
}
