<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductModel;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Products\Domain\ValueObjects\ProductPassword;
use Src\Products\Domain\ValueObjects\TargetUrl;

/**
 * Eloquent model strictly mapped to the `products` table.
 * This model belongs to the Infrastructure layer only — Domain and Application
 * layers must never import this class directly.
 *
 * @property int $id
 * @property int $product_type_id
 * @property int|null $user_id
 * @property string $model
 * @property int|null $linked_to_product_id
 * @property string $password
 * @property string|null $target_url
 * @property int $usage
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property string $configuration_status
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property string|null $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class EloquentProduct extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    /** @var string */
    protected $keyType = 'int';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'product_type_id',
        'user_id',
        'model',
        'linked_to_product_id',
        'password',
        'target_url',
        'usage',
        'name',
        'description',
        'active',
        'configuration_status',
        'assigned_at',
        'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'active' => 'boolean',
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductTypeModel::class, 'product_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function toDomainEntity(): Product
    {
        return Product::fromPersistence(
            id: $this->id,
            productTypeId: $this->product_type_id,
            userId: $this->user_id,
            model: $this->model,
            linkedToProductId: $this->linked_to_product_id,
            password: $this->password,
            targetUrl: $this->target_url,
            usage: $this->usage,
            name: $this->name,
            description: $this->description,
            active: $this->active,
            configurationStatus: ConfigurationStatus::from($this->configuration_status),
            assignedAt: $this->assigned_at
                ? DateTimeImmutable::createFromInterface($this->assigned_at)
                : null,
            size: $this->size,
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
            deletedAt: $this->deleted_at
                ? DateTimeImmutable::createFromInterface($this->deleted_at)
                : null,
        );
    }
}
