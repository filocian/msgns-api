<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

use Src\Products\Domain\Entities\WhatsappLocale;

final readonly class WhatsappLocaleResource
{
    public function __construct(
        public string $code,
    ) {}

    public static function fromEntity(WhatsappLocale $locale): self
    {
        return new self(
            code: $locale->code,
        );
    }
}
