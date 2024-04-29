<?php

declare(strict_types=1);

use App\Static\Permissions\StaticRoles;

describe('App Roles and Permissions', function () {
	it('can create roles', function (string $roleName) {
		$role = $this->createRole($roleName);

		$this->assertEquals($roleName, $role->name);
	})->with('Roles');

	it('can create permissions', function (string $permissionName) {
		$permission = $this->createPermission($permissionName);

		$this->assertEquals($permissionName, $permission->name);
	})->with('Permissions');

	it('can assign BackOffice role permissions', function (string $permissionName) {
		$role = $this->createRole(StaticRoles::BACKOFFICE_ROLE);
		$permission = $this->createPermission($permissionName);
		$role->givePermissionTo($permission);

		$this->assertTrue($role->hasPermissionTo($permission));
	})->with('BackOfficePermissions');

	it('can assign Designer role permissions', function (string $permissionName) {
		$role = $this->createRole(StaticRoles::DESIGNER_ROLE);
		$permission = $this->createPermission($permissionName);
		$role->givePermissionTo($permission);

		$this->assertTrue($role->hasPermissionTo($permission));
	})->with('DesignerPermissions');

	it('can assign Developer role permissions', function (string $permissionName) {
		$role = $this->createRole(StaticRoles::DEV_ROLE);
		$permission = $this->createPermission($permissionName);
		$role->givePermissionTo($permission);

		$this->assertTrue($role->hasPermissionTo($permission));
	})->with('DeveloperPermissions');

	it('can assign Marketing role permissions', function (string $permissionName) {
		$role = $this->createRole(StaticRoles::MARKETING_ROLE);
		$permission = $this->createPermission($permissionName);
		$role->givePermissionTo($permission);

		$this->assertTrue($role->hasPermissionTo($permission));
	})->with('MarketingPermissions');

	it('can assign User role permissions', function (string $permissionName) {
		$role = $this->createRole(StaticRoles::USER_ROLE);
		$permission = $this->createPermission($permissionName);
		$role->givePermissionTo($permission);

		$this->assertTrue($role->hasPermissionTo($permission));
	})->with('UserPermissions');
});
