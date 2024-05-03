<?php

declare(strict_types=1);

use App\Infrastructure\DTO\UserDto;
use Symfony\Component\HttpFoundation\Response as Response;

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

		$response->assertStatus(Response::HTTP_CREATED);
		$this->assertEquals($userData['name'], $responseData['data']['user']['name']);
		$this->assertEquals($userData['email'], $responseData['data']['user']['email']);
	});

	it('User can LogIn', function () {
		$user = $this->create_user([]);
		$userDto = UserDto::fromModel($user);
		$response = $this->postWithHeaders('/api/auth/login', [
			'email' => $user->email,
			'password' => 'Pass123456!',
		]);

		$response->assertStatus(Response::HTTP_OK);
		$response->assertJson([
			'data' => $userDto->toArray('user'),
		]);
	});

	it('User can LogOut', function () {
		$user = $this->create_user([]);
		$this->actingAs($user);
		$response = $this->postWithHeaders('/api/auth/logout');

		$response->assertStatus(Response::HTTP_OK);
		$response->assertJson([
			'data' => true,
		]);
	});
});
