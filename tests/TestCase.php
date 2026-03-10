<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
	/**
	 * Sends a POST request using required auth headers
	 *
	 * @param string $uri
	 * @param array<string, mixed> $data
	 * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
	 */
	public function postWithHeaders(string $uri, array $data = []): TestResponse
	{
		$headers = [
			'Accept' => 'application/json',
		];
		return $this->post($uri, $data, $headers);
	}

	/**
	 * Sends a PUT request using required auth headers
	 *
	 * @param string $uri
	 * @param array<string, mixed> $data
	 * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
	 */
	public function putWithHeaders(string $uri, array $data = []): TestResponse
	{
		$headers = [
			'Accept' => 'application/json',
		];
		return $this->put($uri, $data, $headers);
	}

	/**
	 * Creates a Test user with default information:
	 *
	 * name: Test User
	 *
	 * email: test.user@test.com
	 *
	 * password: Pass123456!
	 *
	 * google_id: null
	 *
	 * @param array{name?: string|null, email?: string, password?: string, google_id?: string|null} $userData
	 */
	public function create_user(array $userData): User
	{
		$defaultUserData = [
			'name' => 'Test User',
			'email' => 'test.user@test.com',
			'password' => 'Pass123456!',
			'google_id' => null,
		];

		return User::factory()->create(array_merge($defaultUserData, $userData));
	}

	/**
	 * Creates a role
	 *
	 * @param string $roleName
	 * @param string $roleGuard
	 * @return \Spatie\Permission\Contracts\Role|Role
	 */
	public function createRole(string $roleName, string $roleGuard = 'stateful-api'): Role|\Spatie\Permission\Contracts\Role
	{
		return Role::findOrCreate($roleName, $roleGuard);
	}

	public function createPermission(
		string $permissionName,
		string $permissionGuard = 'stateful-api'
	): Permission|\Spatie\Permission\Contracts\Permission {
		return Permission::findOrCreate($permissionName, $permissionGuard);
	}
}
