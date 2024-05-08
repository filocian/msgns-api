<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Symfony\Component\HttpFoundation\Response as Response;

describe('Product configuration', function () {
	beforeEach(function () {
		Carbon::setTestNow(CarbonImmutable::create(2024, 1, 1, 1));
		$this->now = Carbon::now()->toISOString();
		$this->seed(DatabaseSeeder::class);
	});

	afterEach(function () {
		Carbon::setTestNow(null);
	});

	it('Unauthenticated user cannot configure a product', function () {
		$response = $this->putWithHeaders('/api/products/1/configure', [
			'configuration' => [
				'configuration' => [
					'target_url' => 'http://test.target.url',
				],
			],
		]);

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('User without permission cannot configure a product', function () {
		$user = User::where('email', 'designer@test.com')->first();
		$this->actingAs($user);
		$response = $this->putWithHeaders('/api/products/1/configure', [
			'configuration' => [
				'target_url' => 'http://test.target.url',
			],
		]);

		$response->assertStatus(Response::HTTP_UNAUTHORIZED);
	});

	it('Non existent product cannot be configured', function () {
		$user = User::where('email', 'user@test.com')->first();
		$this->actingAs($user);
		$response = $this->putWithHeaders('/api/products/1025/configure', [
			'configuration' => [
				'target_url' => 'http://test.target.url',
			],
		]);

		$response->assertStatus(Response::HTTP_NOT_FOUND);
	});

	it('Request validation detect bad configuration array', function () {
		$user = User::where('email', 'backoffice@test.com')->first();
		$this->actingAs($user);
		$response = $this->putWithHeaders('/api/products/1/configure', [
			'config' => [
				'target_url' => 'http://test.target.url',
			],
		]);

		$response->assertStatus(Response::HTTP_BAD_REQUEST);
	});

	it('Authenticated user with permission can configure a product', function () {
		$user = $this->create_user([]);
		$user->assignRole(App\Static\Permissions\StaticRoles::DEV_ROLE);
		Product::findById(1)->update(['user_id' => $user->id]);
		$this->actingAs($user);
		$response = $this->putWithHeaders('/api/products/1/configure', [
			'configuration' => [
				'target_url' => 'http://test.destination.url',
			],
		]);

		$configuredProduct = [
			'data' => [
				'product' => [
					'id' => 1,
					'config' => [
						'target_url_1' => 'http://test.destination.url',
						'image' => 'product_image_path_or_url',
						'image_ref' => 'S-GG-XX-RC',
						'password' => '123456',
						'target_url_2' => 'https://target.url',
						'target_url_3' => 'https://target.url',
						'image_2' => 'product_image_path_or_url',
						'image_3' => 'product_image_path_or_url',
					],
					'type' => [
						'id' => 4,
						'code' => 'google-review-sticker',
						'name' => 'google-review-sticker',
						'description' => 'google-review-sticker',
						'config_template' => [
							'target_url' => 'https://target.url',
							'image' => 'product_image_path_or_url',
							'password' => '123456',
							'target_url_2' => 'https://target.url',
							'target_url_3' => 'https://target.url',
							'image_2' => 'product_image_path_or_url',
							'image_3' => 'product_image_path_or_url',
						],
						'created_at' => $this->now,
						'updated_at' => $this->now,
					],
					'user' => [
						'id' => $user->id,
						'uuid' => $user->uuid,
						'name' => $user->name,
						'email' => $user->email,
						'google_id' => null,
						'created_at' => $this->now,
						'updated_at' => $this->now,
					],
					'active' => false,
					'created_at' => $this->now,
					'updated_at' => $this->now,
				],
			],
		];

		$response->assertJson($configuredProduct);
		$response->assertStatus(Response::HTTP_OK);
	})->skip();

	it('SuperUser can configure a product', function () {
		$user = User::where('email', 'backoffice@test.com')->first();
		$this->actingAs($user);
		$response = $this->putWithHeaders('/api/products/1/configure', [
			'configuration' => [
				'target_url' => 'http://test.target.url',
			],
		]);

		$response->assertStatus(Response::HTTP_OK);
	});
});
