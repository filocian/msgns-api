<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Symfony\Component\HttpFoundation\Response as Response;

describe('Product Activation/Deactivation', function () {
	it('Unauthenticated user cannot activate a product', function () {
		$response = $this->postWithHeaders('/api/products/1/activate');

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('User without permission cannot activate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'designer@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/activate');

		$response->assertStatus(Response::HTTP_FORBIDDEN);
	});

	it('User which is not the owner cannot activate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/activate');

		$response->assertStatus(Response::HTTP_FORBIDDEN);
	});

	it('Non existent product cannot be activated', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1025/register/123456');

		$response->assertStatus(Response::HTTP_NOT_FOUND);
	});

	it('User with permission can activate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/activate');

		$response->assertStatus(Response::HTTP_CREATED);
	});

	it('SuperUser can activate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'backoffice@test.com')->first();
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/activate');

		$response->assertStatus(Response::HTTP_CREATED);
	});

	it('Unauthenticated user cannot deactivate a product', function () {
		$response = $this->postWithHeaders('/api/products/1/deactivate');

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('User without permission cannot deactivate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'designer@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/deactivate');

		$response->assertStatus(Response::HTTP_FORBIDDEN);
	});

	it('User which is not the owner cannot deactivate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/deactivate');

		$response->assertStatus(Response::HTTP_FORBIDDEN);
	});

	it('Non existent product cannot be deactivated', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1025/register/123456');

		$response->assertStatus(Response::HTTP_NOT_FOUND);
	});

	it('User with permission can deactivate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/deactivate');

		$response->assertStatus(Response::HTTP_CREATED);
	});

	it('SuperUser can deactivate a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'backoffice@test.com')->first();
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/deactivate');

		$response->assertStatus(Response::HTTP_CREATED);
	});
});
