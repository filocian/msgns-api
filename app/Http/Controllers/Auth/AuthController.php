<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\SignupRequest;
use App\UseCases\Auth\CurrentUserUC;
use App\UseCases\Auth\GoogleLoginUC;
use App\UseCases\Auth\LoginUC;
use App\UseCases\Auth\LogoutUC;
use App\UseCases\Auth\SignUpUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
	public function __construct(
		readonly private GoogleLoginUC $googleLoginUC,
		readonly private LoginUC $loginUC,
		readonly private SignUpUC $signUpUC,
		readonly private LogoutUC $logoutUC,
		readonly private CurrentUserUC $currentUserUC,
	) {}

	public function signUp(SignupRequest $request): JsonResponse
	{
		$data = [
			'email' => $request->get('email'),
			'name' => $request->get('name'),
			'password' => $request->get('password'),
		];

		$user = $this->signUpUC->run($data);

		return HttpJson::OK(
			$user->toArray('user'),
			Response::HTTP_CREATED
		);
	}


	public function login(LoginRequest $request): JsonResponse
	{
		$data = [
			'email' => $request->get('email'),
			'password' => $request->get('password'),
		];

		$user = $this->loginUC->run($data);
		return HttpJson::OK($user->toArray('user'));
	}


	public function googleLogin(GoogleLoginRequest $request): JsonResponse
	{
		$user = $this->googleLoginUC->run($request->get('token'));
		return HttpJson::OK($user->wrapped('user'));
	}

	public function logout(LogoutRequest $request): JsonResponse
	{
		$bye = $this->logoutUC->run();
		return HttpJson::OK($bye);
	}

	public function currentUser(Request $request): JsonResponse
	{
		$user = $this->currentUserUC->run();

		return HttpJson::OK($user);
	}

	public function hello(): JsonResponse
	{
		return HttpJson::OK('hello');
	}
}
