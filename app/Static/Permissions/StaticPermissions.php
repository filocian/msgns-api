<?php

declare(strict_types=1);

namespace App\Static\Permissions;

use Src\Identity\Domain\Permissions\DomainPermissions;

/**
 * Thin adapter over DomainPermissions.
 *
 * This class is kept for backward compatibility with legacy code that
 * references App\Static\Permissions\StaticPermissions. All logic now
 * delegates to the domain layer. Adding a constant to DomainPermissions
 * automatically makes it available here with zero changes.
 *
 * Direction: App\ → Src\ is explicitly allowed (App adapts to Src).
 */
final class StaticPermissions
{
	/*
	 * Permisos para la aplicación:
	 *
	 * Asumiendo que podemos discriminar por permisos y roles, creo que resulta interesante adoptar una política de
	 * permisos basdos en acciones, y en caso de ser necesario, limitar las vistas o información en las respuestas
	 * basándonos en la conjunción de permiso de acción + rol asignado.
	 *
	 * De ésta manera se reducen considerablemente el número de permisos necesarios y se simplifica bastante la
	 * gestión del sistema de permisos. Remarcar que esto implicará un poco de complejidad extra en tiempo de desarrollo
	 * para aquellos casos en los que necesitemos sesgar la información devuelta en función de un rol.
	 *
	 * */

	// Pass-through constants kept for legacy code that references them directly.
	// Do NOT add new constants here — add them to DomainPermissions instead.
	public const string CREATE_PRODUCT_TYPE = DomainPermissions::CREATE_PRODUCT_TYPE;
	public const string EDIT_PRODUCT_TYPE = DomainPermissions::EDIT_PRODUCT_TYPE;
	public const string SINGLE_PRODUCT_ACTIVATION = DomainPermissions::SINGLE_PRODUCT_ACTIVATION;
	public const string SINGLE_PRODUCT_DEACTIVATION = DomainPermissions::SINGLE_PRODUCT_DEACTIVATION;
	public const string BULK_PRODUCT_ACTIVATION = DomainPermissions::BULK_PRODUCT_ACTIVATION;
	public const string BULK_PRODUCT_DEACTIVATION = DomainPermissions::BULK_PRODUCT_DEACTIVATION;
	public const string SINGLE_PRODUCT_ASSIGNMENT = DomainPermissions::SINGLE_PRODUCT_ASSIGNMENT;
	public const string BULK_PRODUCT_ASSIGNMENT = DomainPermissions::BULK_PRODUCT_ASSIGNMENT;
	public const string SINGLE_PRODUCT_CONFIGURATION = DomainPermissions::SINGLE_PRODUCT_CONFIGURATION;
	public const string BULK_PRODUCT_CONFIGURATION = DomainPermissions::BULK_PRODUCT_CONFIGURATION;
	public const string CREATE_BUSINESS = DomainPermissions::CREATE_BUSINESS;
	public const string EDIT_BUSINESS = DomainPermissions::EDIT_BUSINESS;
	public const string SINGLE_PRODUCT_BUSINESS_ASSIGNMENT = DomainPermissions::SINGLE_PRODUCT_BUSINESS_ASSIGNMENT;
	public const string BULK_PRODUCT_BUSINESS_ASSIGNMENT = DomainPermissions::BULK_PRODUCT_BUSINESS_ASSIGNMENT;
	public const string PRODUCT_GENERATION = DomainPermissions::PRODUCT_GENERATION;
	public const string CREATE_ROLE = DomainPermissions::CREATE_ROLE;
	public const string EDIT_ROLE = DomainPermissions::EDIT_ROLE;
	public const string ASSIGN_ROLE = DomainPermissions::ASSIGN_ROLE;
	public const string CREATE_PERMISSION = DomainPermissions::CREATE_PERMISSION;
	public const string EDIT_PERMISSION = DomainPermissions::EDIT_PERMISSION;
	public const string ASSIGN_PERMISSION = DomainPermissions::ASSIGN_PERMISSION;
	public const string EDIT_USER = DomainPermissions::EDIT_USER;
	public const string EXPORT_DATA = DomainPermissions::EXPORT_DATA;
	public const string MANAGE_ROLES_AND_PERMISSIONS = DomainPermissions::MANAGE_ROLES_AND_PERMISSIONS;
	public const string MANAGE_SUBSCRIPTION_TYPES = DomainPermissions::MANAGE_SUBSCRIPTION_TYPES;

	/**
	 * Delegates to DomainPermissions::all().
	 *
	 * Any new permission added to DomainPermissions::all() is automatically
	 * included here — no changes required in this file.
	 *
	 * @return string[]
	 */
	public static function all(): array
	{
		return DomainPermissions::all();
	}
}
