<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\WhatsappLocale;

interface WhatsappLocaleRepositoryPort
{
    public function findByCode(string $code): ?WhatsappLocale;

    /** @return list<WhatsappLocale> */
    public function findAll(): array;
}
