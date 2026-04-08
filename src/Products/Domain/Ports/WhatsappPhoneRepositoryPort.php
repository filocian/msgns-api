<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\WhatsappPhone;

interface WhatsappPhoneRepositoryPort
{
    public function findById(int $id): ?WhatsappPhone;

    /** @return list<WhatsappPhone> */
    public function findByProductId(int $productId): array;

    public function save(WhatsappPhone $phone): WhatsappPhone;

    public function delete(int $id): void;

    public function countByProductId(int $productId): int;
}
