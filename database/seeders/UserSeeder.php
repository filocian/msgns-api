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

		if (Role::where('name', StaticRoles::DEV_ROLE)->exists()) {
			$user->assignRole(StaticRoles::DEV_ROLE);
		}

		$user = User::create([
			'name' => 'Backoffice Test User',
			'email' => 'backoffice@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if (Role::where('name', StaticRoles::BACKOFFICE_ROLE)->exists()) {
			$user->assignRole(StaticRoles::BACKOFFICE_ROLE);
		}

		$user = User::create([
			'name' => 'Marketing Test User',
			'email' => 'marketing@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if (Role::where('name', StaticRoles::MARKETING_ROLE)->exists()) {
			$user->assignRole(StaticRoles::MARKETING_ROLE);
		}

		$user = User::create([
			'name' => 'Designer Test User',
			'email' => 'designer@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if (Role::where('name', StaticRoles::DESIGNER_ROLE)->exists()) {
			$user->assignRole(StaticRoles::DESIGNER_ROLE);
		}

		$user = User::create([
			'name' => 'User',
			'email' => 'user@test.com',
			'email_verified_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
			'password' => Hash::make('daPassword123!'),
		]);

		if (Role::where('name', StaticRoles::USER_ROLE)->exists()) {
			$user->assignRole(StaticRoles::USER_ROLE);
		}
	}
}
