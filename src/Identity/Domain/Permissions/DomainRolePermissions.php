<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Permissions;

/**
 * Domain-owned RBAC role-permission mappings.
 *
 * SYNC NOTE: This file mirrors app/Static/Permissions/StaticRolePermissions.php.
 * Keep in sync until the legacy app/ layer is fully retired.
 * Drift is caught by DomainStaticParityTest.
 */
final class DomainRolePermissions
{
    /** @return array{role: string, permissions: string[]} */
    public static function devRolePermissions(): array
    {
        return [
            'role' => DomainRoles::DEV_ROLE,
            'permissions' => DomainPermissions::all(),
        ];
    }

    /** @return array{role: string, permissions: string[]} */
    public static function backOfficeRolePermissions(): array
    {
        return [
            'role' => DomainRoles::BACKOFFICE_ROLE,
            'permissions' => DomainPermissions::all(),
        ];
    }

    /** @return array{role: string, permissions: string[]} */
    public static function designerRolePermissions(): array
    {
        return [
            'role' => DomainRoles::DESIGNER_ROLE,
            'permissions' => [DomainPermissions::EXPORT_DATA],
        ];
    }

    /** @return array{role: string, permissions: string[]} */
    public static function marketingRolePermissions(): array
    {
        return [
            'role' => DomainRoles::MARKETING_ROLE,
            'permissions' => [DomainPermissions::EXPORT_DATA],
        ];
    }

    /** @return array{role: string, permissions: string[]} */
    public static function userRolePermissions(): array
    {
        return [
            'role' => DomainRoles::USER_ROLE,
            'permissions' => [
                DomainPermissions::SINGLE_PRODUCT_ACTIVATION,
                DomainPermissions::SINGLE_PRODUCT_DEACTIVATION,
                DomainPermissions::SINGLE_PRODUCT_ASSIGNMENT,
                DomainPermissions::SINGLE_PRODUCT_CONFIGURATION,
                DomainPermissions::CREATE_BUSINESS,
                DomainPermissions::EDIT_BUSINESS,
                DomainPermissions::SINGLE_PRODUCT_BUSINESS_ASSIGNMENT,
                DomainPermissions::EDIT_USER,
            ],
        ];
    }
}
