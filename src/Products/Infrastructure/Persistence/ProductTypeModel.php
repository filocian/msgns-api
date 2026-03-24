<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Src\Products\Domain\Entities\ProductType;

/**
 * Eloquent model strictly mapped to the `product_types` table.
 * This model belongs to the Infrastructure layer only — Domain and Application
 * layers must never import this class directly.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $primary_model
 * @property string|null $secondary_model
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ProductTypeModel extends Model
{
    protected $table = 'product_types';

    /** @var string */
    protected $keyType = 'int';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'image_ref',
        'primary_model',
        'secondary_model',
    ];

    public function toDomainEntity(): ProductType
    {
        return ProductType::fromPersistence(
            id: $this->id,
            code: $this->code,
            name: $this->name,
            primaryModel: $this->primary_model,
            secondaryModel: $this->secondary_model,
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
        );
    }
}
