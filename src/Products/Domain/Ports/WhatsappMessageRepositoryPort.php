<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\WhatsappMessage;

interface WhatsappMessageRepositoryPort
{
    public function findById(int $id): ?WhatsappMessage;

    /** @return list<WhatsappMessage> */
    public function findByProductId(int $productId): array;

    /** @return list<WhatsappMessage> */
    public function findByPhoneId(int $phoneId): array;

    public function save(WhatsappMessage $message): WhatsappMessage;

    public function delete(int $id): void;

    public function countByProductId(int $productId): int;

    public function existsByPhoneIdAndLocaleId(int $phoneId, int $localeId): bool;

    public function clearDefaultsForProduct(int $productId): void;

    /**
     * Find the best message for redirection resolution.
     * Priority: (1) default message, (2) locale-matching, (3) first available.
     * Returns message with joined phone data (phone, prefix) and locale code.
     */
    public function findForResolution(int $productId, ?string $localePrefix): ?WhatsappMessage;
}
