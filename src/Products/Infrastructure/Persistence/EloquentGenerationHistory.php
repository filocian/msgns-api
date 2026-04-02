<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Products\Domain\Entities\GenerationHistory;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $generated_at
 * @property int $total_count
 * @property array<int, array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}> $summary
 * @property string $excel_blob
 * @property int|null $generated_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EloquentGenerationHistory extends Model
{
    protected $table = 'generation_history';

    /** @var string */
    protected $keyType = 'int';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'generated_at',
        'total_count',
        'summary',
        'excel_blob',
        'generated_by_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'generated_at' => 'datetime',
        'summary' => 'array',
        'total_count' => 'integer',
    ];

    /** @var list<string> */
    protected $hidden = ['excel_blob'];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function toDomainEntity(): GenerationHistory
    {
        return GenerationHistory::fromPersistence(
            id: $this->id,
            generatedAt: DateTimeImmutable::createFromInterface($this->generated_at),
            totalCount: $this->total_count,
            summaryData: $this->summary,
            excelBlob: (string) $this->getAttribute('excel_blob'),
            generatedById: $this->generated_by_id,
            createdAt: $this->created_at ? DateTimeImmutable::createFromInterface($this->created_at) : new DateTimeImmutable(),
            updatedAt: $this->updated_at ? DateTimeImmutable::createFromInterface($this->updated_at) : new DateTimeImmutable(),
        );
    }
}
