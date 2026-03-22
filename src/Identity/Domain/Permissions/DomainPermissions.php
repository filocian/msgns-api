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
        ];
    }
}
