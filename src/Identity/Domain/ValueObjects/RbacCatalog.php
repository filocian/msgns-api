<?php

declare(strict_types=1);

namespace Src\Identity\Domain\ValueObjects;

use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRolePermissions;
use Src\Identity\Domain\Permissions\DomainRoles;

/**
 * Domain-owned RBAC Catalog.
 *
 * This is the single source of truth for v2 code that needs roles, permissions,
 * and their mappings. It delegates to domain-owned permission classes under
 * Src\Identity\Domain\Permissions\.
 *
 * All 5 roles and 26 permissions are code-defined (not user-editable in Phase 1).
 * Core roles receive additional protection via CoreRolePolicy.
 */
final class RbacCatalog
{
    // -----------------------------------------------------------------
    // Core roles — protected against deletion / rename
    // -----------------------------------------------------------------
    /** @return string[] */
    public static function coreRoleNames(): array
    {
        return [DomainRoles::DEV_ROLE, DomainRoles::BACKOFFICE_ROLE, DomainRoles::USER_ROLE];
    }

    // -----------------------------------------------------------------
    // Default role assigned on registration
    // -----------------------------------------------------------------
    public static function defaultRole(): string
    {
        return DomainRoles::USER_ROLE;
    }

    // -----------------------------------------------------------------
    // Full catalog — 5 roles with their permission mappings
    // -----------------------------------------------------------------
    /** @return RoleCatalogEntry[] */
    public static function entries(): array
    {
        $rolePermissionMethods = [
            DomainRolePermissions::devRolePermissions(),
            DomainRolePermissions::backOfficeRolePermissions(),
            DomainRolePermissions::designerRolePermissions(),
            DomainRolePermissions::marketingRolePermissions(),
            DomainRolePermissions::userRolePermissions(),
        ];

        $coreRoles = self::coreRoleNames();

        return array_map(
            fn(array $rp) => new RoleCatalogEntry(
                name: $rp['role'],
                permissions: $rp['permissions'],
                isCore: in_array($rp['role'], $coreRoles, true),
            ),
            $rolePermissionMethods,
        );
    }

    /** @return string[] */
    public static function roleNames(): array
    {
        return array_map(fn(RoleCatalogEntry $e) => $e->name, self::entries());
    }

    /** @return string[] */
    public static function allPermissions(): array
    {
        return DomainPermissions::all();
    }
}
