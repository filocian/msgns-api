<?php

declare(strict_types=1);

namespace App\Static\Permissions;

final class StaticRolePermissions
{
	public static function devRolePermissions(): array
	{
		return [
			'role' => StaticRoles::DEV_ROLE,
			'permissions' => StaticPermissions::all(),
		];
	}
	public static function backOfficeRolePermissions(): array
	{
		return [
			'role' => StaticRoles::BACKOFFICE_ROLE,
			'permissions' => StaticPermissions::all(),
		];
	}

	public static function designerRolePermissions(): array
	{
		return [
			'role' => StaticRoles::DESIGNER_ROLE,
			'permissions' => [StaticPermissions::EXPORT_DATA],
		];
	}

	public static function marketingRolePermissions(): array
	{
		return [
			'role' => StaticRoles::MARKETING_ROLE,
			'permissions' => [StaticPermissions::EXPORT_DATA],
		];
	}

	public static function userRolePermissions(): array
	{
		return [
			'role' => StaticRoles::USER_ROLE,
			'permissions' => [
				StaticPermissions::SINGLE_PRODUCT_ACTIVATION,
				StaticPermissions::SINGLE_PRODUCT_DEACTIVATION,
				StaticPermissions::SINGLE_PRODUCT_ASSIGNMENT,
				StaticPermissions::SINGLE_PRODUCT_CONFIGURATION,
				StaticPermissions::CREATE_BUSINESS,
				StaticPermissions::EDIT_BUSINESS,
				StaticPermissions::SINGLE_PRODUCT_BUSINESS_ASSIGNMENT,
				StaticPermissions::EDIT_USER,
			],
		];
	}
}
