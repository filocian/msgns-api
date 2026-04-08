<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\WhatsappPhone;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;

final class EloquentWhatsappPhoneRepository implements WhatsappPhoneRepositoryPort
{
    public function findById(int $id): ?WhatsappPhone
    {
        $model = EloquentWhatsappPhone::find($id);

        return $model?->toDomainEntity();
    }

    /** @return list<WhatsappPhone> */
    public function findByProductId(int $productId): array
    {
        return EloquentWhatsappPhone::where('product_id', $productId)
            ->orderBy('id')
            ->get()
            ->map(fn (EloquentWhatsappPhone $model): WhatsappPhone => $model->toDomainEntity())
            ->values()
            ->all();
    }

    public function save(WhatsappPhone $phone): WhatsappPhone
    {
        if ($phone->id === 0) {
            $model = EloquentWhatsappPhone::create([
                'product_id' => $phone->productId,
                'phone' => $phone->phone,
                'prefix' => $phone->prefix,
            ]);

            return $model->toDomainEntity();
        }

        $model = EloquentWhatsappPhone::findOrFail($phone->id);
        $model->update([
            'phone' => $phone->phone,
            'prefix' => $phone->prefix,
        ]);

        return $model->toDomainEntity();
    }

    public function delete(int $id): void
    {
        EloquentWhatsappPhone::destroy($id);
    }

    public function countByProductId(int $productId): int
    {
        return EloquentWhatsappPhone::where('product_id', $productId)->count();
    }
}
