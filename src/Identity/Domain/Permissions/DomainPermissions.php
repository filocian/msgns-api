<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Permissions;

/**
 * Domain-owned RBAC permission constants.
 *
 * SYNC NOTE: This file mirrors app/Static/Permissions/StaticPermissions.php.
 * Keep in sync until the legacy app/ layer is fully retired.
 * Drift is caught by DomainStaticParityTest.
 */
final class DomainPermissions
{
    public const string CREATE_PRODUCT_TYPE = 'create_product_type';
    public const string EDIT_PRODUCT_TYPE = 'edit_product_type';
    public const string SINGLE_PRODUCT_ACTIVATION = 'single_product_activation';
    public const string SINGLE_PRODUCT_DEACTIVATION = 'single_product_deactivation';
    public const string BULK_PRODUCT_ACTIVATION = 'bulk_product_activation';
    public const string BULK_PRODUCT_DEACTIVATION = 'bulk_product_deactivation';
    public const string SINGLE_PRODUCT_ASSIGNMENT = 'single_product_assignment';
    public const string BULK_PRODUCT_ASSIGNMENT = 'bulk_product_assignment';
    public const string SINGLE_PRODUCT_CONFIGURATION = 'single_product_configuration';
    public const string BULK_PRODUCT_CONFIGURATION = 'bulk_product_configuration';
    public const string CREATE_BUSINESS = 'create_business';
    public const string EDIT_BUSINESS = 'edit_business';
    public const string SINGLE_PRODUCT_BUSINESS_ASSIGNMENT = 'single_product_business_assignment';
    public const string BULK_PRODUCT_BUSINESS_ASSIGNMENT = 'bulk_product_business_assignment';
    public const string PRODUCT_GENERATION = 'product_generation';
    public const string CREATE_ROLE = 'create_role';
    public const string EDIT_ROLE = 'edit_role';
    public const string ASSIGN_ROLE = 'assign_role';
    public const string CREATE_PERMISSION = 'create_permission';
    public const string EDIT_PERMISSION = 'edit_permission';
    public const string ASSIGN_PERMISSION = 'assign_permission';
    public const string EDIT_USER = 'edit_user';
    public const string EXPORT_DATA = 'export_data';
    public const string MANAGE_ROLES_AND_PERMISSIONS = 'manage_roles_and_permissions';

    /**
     * Human-readable English descriptions for every permission.
     *
     * Every value returned by all() MUST have a corresponding entry here.
     * Enforced by DomainPermissionsDescriptionsTest.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::CREATE_PRODUCT_TYPE                => 'Create new product types',
            self::EDIT_PRODUCT_TYPE                  => 'Edit existing product types',
            self::SINGLE_PRODUCT_ACTIVATION          => 'Activate a single product',
            self::SINGLE_PRODUCT_DEACTIVATION        => 'Deactivate a single product',
            self::BULK_PRODUCT_ACTIVATION            => 'Activate products in bulk',
            self::BULK_PRODUCT_DEACTIVATION          => 'Deactivate products in bulk',
            self::SINGLE_PRODUCT_ASSIGNMENT          => 'Assign a single product to a user',
            self::BULK_PRODUCT_ASSIGNMENT            => 'Assign products to users in bulk',
            self::SINGLE_PRODUCT_CONFIGURATION       => 'Configure a single product',
            self::BULK_PRODUCT_CONFIGURATION         => 'Configure products in bulk',
            self::CREATE_BUSINESS                    => 'Create new businesses',
            self::EDIT_BUSINESS                      => 'Edit existing businesses',
            self::SINGLE_PRODUCT_BUSINESS_ASSIGNMENT => 'Assign a single product to a business',
            self::BULK_PRODUCT_BUSINESS_ASSIGNMENT   => 'Assign products to businesses in bulk',
            self::PRODUCT_GENERATION                 => 'Generate products',
            self::CREATE_ROLE                        => 'Create new custom roles',
            self::EDIT_ROLE                          => 'Edit existing roles',
            self::ASSIGN_ROLE                        => 'Assign roles to users',
            self::CREATE_PERMISSION                  => 'Create new permissions',
            self::EDIT_PERMISSION                    => 'Edit existing permissions',
            self::ASSIGN_PERMISSION                  => 'Assign permissions to roles',
            self::EDIT_USER                          => 'Edit user profiles',
            self::EXPORT_DATA                        => 'Export data from the system',
            self::MANAGE_ROLES_AND_PERMISSIONS       => 'Manage roles and permissions (admin RBAC panel access)',
        ];
    }

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::CREATE_PRODUCT_TYPE,
            self::EDIT_PRODUCT_TYPE,
            self::SINGLE_PRODUCT_ACTIVATION,
            self::SINGLE_PRODUCT_DEACTIVATION,
            self::BULK_PRODUCT_ACTIVATION,
            self::BULK_PRODUCT_DEACTIVATION,
            self::SINGLE_PRODUCT_ASSIGNMENT,
            self::BULK_PRODUCT_ASSIGNMENT,
            self::SINGLE_PRODUCT_CONFIGURATION,
            self::BULK_PRODUCT_CONFIGURATION,
            self::CREATE_BUSINESS,
            self::EDIT_BUSINESS,
            self::SINGLE_PRODUCT_BUSINESS_ASSIGNMENT,
            self::BULK_PRODUCT_BUSINESS_ASSIGNMENT,
            self::PRODUCT_GENERATION,
            self::CREATE_ROLE,
            self::EDIT_ROLE,
            self::ASSIGN_ROLE,
            self::CREATE_PERMISSION,
            self::EDIT_PERMISSION,
            self::ASSIGN_PERMISSION,
            self::EDIT_USER,
            self::EXPORT_DATA,
            self::MANAGE_ROLES_AND_PERMISSIONS,
        ];
    }
}
