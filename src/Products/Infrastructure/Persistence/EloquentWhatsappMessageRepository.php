<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;

final class EloquentWhatsappMessageRepository implements WhatsappMessageRepositoryPort
{
    public function findById(int $id): ?WhatsappMessage
    {
        $model = EloquentWhatsappMessage::with(['phone', 'locale'])->find($id);

        return $model?->toDomainEntity();
    }

    /** @return list<WhatsappMessage> */
    public function findByProductId(int $productId): array
    {
        return EloquentWhatsappMessage::with(['phone', 'locale'])
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get()
            ->map(fn (EloquentWhatsappMessage $model): WhatsappMessage => $model->toDomainEntity())
            ->values()
            ->all();
    }

    /** @return list<WhatsappMessage> */
    public function findByPhoneId(int $phoneId): array
    {
        return EloquentWhatsappMessage::with(['phone', 'locale'])
            ->where('phone_id', $phoneId)
            ->orderBy('id')
            ->get()
            ->map(fn (EloquentWhatsappMessage $model): WhatsappMessage => $model->toDomainEntity())
            ->values()
            ->all();
    }

    public function save(WhatsappMessage $message): WhatsappMessage
    {
        if ($message->id === 0) {
            $model = EloquentWhatsappMessage::create([
                'product_id' => $message->productId,
                'phone_id' => $message->phoneId,
                'locale_id' => $message->localeId,
                'message' => $message->message,
                'default' => $message->isDefault,
            ]);

            $model->load(['phone', 'locale']);

            return $model->toDomainEntity();
        }

        $model = EloquentWhatsappMessage::findOrFail($message->id);
        $model->update([
            'message' => $message->message,
            'default' => $message->isDefault,
        ]);

        $model->load(['phone', 'locale']);

        return $model->toDomainEntity();
    }

    public function delete(int $id): void
    {
        EloquentWhatsappMessage::destroy($id);
    }

    public function countByProductId(int $productId): int
    {
        return EloquentWhatsappMessage::where('product_id', $productId)->count();
    }

    public function existsByPhoneIdAndLocaleId(int $phoneId, int $localeId): bool
    {
        return EloquentWhatsappMessage::where('phone_id', $phoneId)
            ->where('locale_id', $localeId)
            ->exists();
    }

    public function clearDefaultsForProduct(int $productId): void
    {
        EloquentWhatsappMessage::where('product_id', $productId)
            ->where('default', true)
            ->update(['default' => false]);
    }

    public function findForResolution(int $productId, ?string $localePrefix): ?WhatsappMessage
    {
        $query = EloquentWhatsappMessage::with(['phone', 'locale'])
            ->where('whatsapp_messages.product_id', $productId);

        if ($localePrefix !== null && $localePrefix !== '') {
            // Order by: is_default DESC, locale match DESC, id ASC
            $query->leftJoin('whatsapp_locales', 'whatsapp_messages.locale_id', '=', 'whatsapp_locales.id')
                ->selectRaw('whatsapp_messages.*, CASE WHEN whatsapp_locales.code LIKE ? THEN 1 ELSE 0 END as locale_match', [$localePrefix . '%'])
                ->orderByDesc('whatsapp_messages.default')
                ->orderByDesc('locale_match')
                ->orderBy('whatsapp_messages.id');
        } else {
            $query->orderByDesc('whatsapp_messages.default')
                ->orderBy('whatsapp_messages.id');
        }

        $model = $query->first();

        return $model?->toDomainEntity();
    }
}
