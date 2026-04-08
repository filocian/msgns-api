<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\WhatsappLocale;
use Src\Products\Domain\Ports\WhatsappLocaleRepositoryPort;

final class EloquentWhatsappLocaleRepository implements WhatsappLocaleRepositoryPort
{
    public function findByCode(string $code): ?WhatsappLocale
    {
        $model = EloquentWhatsappLocale::where('code', $code)->first();

        return $model?->toDomainEntity();
    }

    /** @return list<WhatsappLocale> */
    public function findAll(): array
    {
        return EloquentWhatsappLocale::orderBy('code')
            ->get()
            ->map(fn (EloquentWhatsappLocale $model): WhatsappLocale => $model->toDomainEntity())
            ->values()
            ->all();
    }
}
