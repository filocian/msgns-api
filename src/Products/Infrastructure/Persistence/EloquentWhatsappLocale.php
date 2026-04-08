<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Src\Products\Domain\Entities\WhatsappLocale;

/**
 * @property int $id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EloquentWhatsappLocale extends Model
{
    protected $table = 'whatsapp_locales';

    /** @var list<string> */
    protected $fillable = [
        'code',
    ];

    public function toDomainEntity(): WhatsappLocale
    {
        return WhatsappLocale::fromPersistence(
            id: $this->id,
            code: $this->code,
        );
    }
}
