<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class UserSeeder extends Seeder
{
	private $table = 'users';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$now = Carbon::now();

		$user = User::create([
			'name' => 'Dev Test User',
			'email' => 'test@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if ($role = Role::findByName(StaticRoles::DEV_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}

		$user = User::create([
			'name' => 'Backoffice Test User',
			'email' => 'backoffice@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if ($role = Role::findByName(StaticRoles::BACKOFFICE_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}

		$user = User::create([
			'name' => 'Marketing Test User',
			'email' => 'marketing@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if ($role = Role::findByName(StaticRoles::MARKETING_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}

		$user = User::create([
			'name' => 'Designer Test User',
			'email' => 'designer@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if ($role = Role::findByName(StaticRoles::DESIGNER_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}

		$user = User::create([
			'name' => 'User',
			'email' => 'user@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if ($role = Role::findByName(StaticRoles::USER_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}
	}
}
