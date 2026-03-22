<?php

declare(strict_types=1);

namespace Src\Identity\Application\Contracts;

interface LocaleMapper
{
    public function mapLanguageToLocale(?string $language): string;
}
