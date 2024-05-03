<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Static\Permissions\StaticRolePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionsSeeder extends Seeder
{
	protected string $table = 'product_types';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
		$roleMethods = [
			'devRolePermissions',
			'backOfficeRolePermissions',
			'designerRolePermissions',
			'marketingRolePermissions',
			'userRolePermissions',
		];

		foreach ($roleMethods as $method) {
			$roleData = StaticRolePermissions::$method();

			$role = Role::findOrCreate($roleData['role'], 'stateful-api');

			foreach ($roleData['permissions'] as $permName) {
				$permission = Permission::findOrCreate($permName, 'stateful-api');
				$role->givePermissionTo($permission);
			}
		}
	}
}
