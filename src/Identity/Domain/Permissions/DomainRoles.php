<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Permissions;

/**
 * Domain-owned RBAC role constants.
 *
 * SYNC NOTE: This file mirrors app/Static/Permissions/StaticRoles.php.
 * Keep in sync until the legacy app/ layer is fully retired.
 * These constants are the canonical source of truth for the Identity Domain layer.
 * Drift is caught by DomainStaticParityTest.
 */
final class DomainRoles
{
    public const string DEV_ROLE = 'developer';
    public const string BACKOFFICE_ROLE = 'backoffice';
    public const string DESIGNER_ROLE = 'designer';
    public const string MARKETING_ROLE = 'marketing';
    public const string USER_ROLE = 'user';

    public const string GUARD = 'stateful-api';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::DEV_ROLE,
            self::BACKOFFICE_ROLE,
            self::DESIGNER_ROLE,
            self::MARKETING_ROLE,
            self::USER_ROLE,
        ];
    }
}
