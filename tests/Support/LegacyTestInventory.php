<?php

declare(strict_types=1);

namespace Tests\Support;

final class LegacyTestInventory
{
    /**
     * @return list<string>
     */
    public static function files(): array
    {
        return [
            'Feature/Identity/LegacyFormRequestAuthorizationTest.php',
            'Feature/ProductRegistrationTest.php',
            'Unit/Identity/Infrastructure/LegacyLocaleMapperTest.php',
        ];
    }
}
