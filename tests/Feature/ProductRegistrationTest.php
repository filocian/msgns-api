<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Symfony\Component\HttpFoundation\Response as Response;

describe('Product Registration', function () {
	it('Unauthenticated user cannot register a product', function () {
		$response = $this->postWithHeaders('/api/products/1/register/123456');

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('User without permission cannot register a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'designer@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/register/123456');

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('Already registered product cannot be registered', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/register/123456');

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('Non existent product cannot be registered', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1025/register/123456');

		$response->assertStatus(Response::HTTP_NOT_FOUND);
	});

	it('Authenticated user with permission can register a product', function () {
		$this->seed(DatabaseSeeder::class);

		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/products/1/register/123456');

		$response->assertStatus(Response::HTTP_CREATED);
	});
});
