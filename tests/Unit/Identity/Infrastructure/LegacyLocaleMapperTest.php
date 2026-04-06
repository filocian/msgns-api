<?php

declare(strict_types=1);

use Src\Identity\Infrastructure\Localization\LegacyLocaleMapper;

it('maps supported languages and falls back to en_UK', function (?string $language, string $expected) {
    $mapper = new LegacyLocaleMapper();

    expect($mapper->mapLanguageToLocale($language))->toBe($expected)
        ->not->toBe('en');
})->with([
    ['ca', 'ca_ES'],
    ['es', 'es_ES'],
    ['fr', 'fr_FR'],
    ['de', 'de_DE'],
    ['it', 'it_IT'],
    ['en', 'en_UK'],
    ['unknown', 'en_UK'],
    [null, 'en_UK'],
])->group('legacy');
