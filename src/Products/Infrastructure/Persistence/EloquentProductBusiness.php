<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Products\Domain\Entities\ProductBusiness;

/**
 * Eloquent model mapped to the `product_business` table.
 * This model belongs to the Infrastructure layer only.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $user_id
 * @property bool $not_a_business
 * @property string|null $name
 * @property array $types
 * @property array|null $place_types
 * @property string|null $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EloquentProductBusiness extends Model
{
    protected $table = 'product_business';

    /** @var string */
    protected $keyType = 'int';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'user_id',
        'not_a_business',
        'name',
        'types',
        'place_types',
        'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'not_a_business' => 'boolean',
        'types' => 'array',
        'place_types' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function toDomainEntity(): ProductBusiness
    {
        return ProductBusiness::fromPersistence(
            id: $this->id,
            productId: $this->product_id,
            userId: $this->user_id ?? 0,
            notABusiness: $this->not_a_business,
            name: $this->name,
            types: $this->types ?? [],
            placeTypes: $this->place_types,
            size: $this->size,
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
        );
    }
}
