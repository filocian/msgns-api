<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Localization;

use Src\Identity\Application\Contracts\LocaleMapper;

final class LegacyLocaleMapper implements LocaleMapper
{
    public function mapLanguageToLocale(?string $language): string
    {
        $normalized = strtolower(trim((string) $language));

        return match ($normalized) {
            'ca' => 'ca_ES',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            default => 'en_UK',
        };
    }
}
