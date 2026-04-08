<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureWhatsappProduct;

use Src\Products\Application\Commands\ConfigureProductCommand;

final readonly class ConfigureWhatsappProductCommand implements ConfigureProductCommand
{
    public function __construct(
        public int $productId,
        public string $phone,
        public string $prefix,
        public string $message,
        public string $localeCode,
    ) {}

    public function productId(): int
    {
        return $this->productId;
    }

    public function commandName(): string
    {
        return 'products.configure_whatsapp_product';
    }
}
