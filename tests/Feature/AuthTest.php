<?php

declare(strict_types=1);

use App\Infrastructure\DTO\UserDto;

describe('App Auth', function () {
	it('User can SignUp', function () {
		$userData = [
			'name' => 'User',
			'email' => 'user@test.com',
			'password' => 'Pass123456!',
			'repeat_password' => 'Pass123456!',
		];
		$response = $this->postWithHeaders('/api/auth/sign-up', $userData);
		$responseData = $response->json();

		$response->assertStatus(201);
		$this->assertEquals($userData['name'], $responseData['data']['user']['name']);
		$this->assertEquals($userData['email'], $responseData['data']['user']['email']);
	});

	it('User can LogIn', function () {
		$user = $this->createUser([]);
		$userDto = UserDto::from($user);
		$response = $this->postWithHeaders('/api/auth/login', [
			'email' => $user->email,
			'password' => 'Pass123456!',
		]);

		$response->assertStatus(200);
		$response->assertJson([
			'data' => ['user' => $userDto->toArray()],
		]);
	});

	it('User can LogOut', function () {
		$user = $this->createUser([]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/auth/logout');

		$response->assertStatus(200);
		$response->assertJson([
			'data' => true,
		]);
	});
});
