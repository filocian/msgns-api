<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\SignupRequest;
use App\Infrastructure\Services\User\UserService;
use App\UseCases\Auth\CurrentUserUC;
use App\UseCases\Auth\GoogleLoginUC;
use App\UseCases\Auth\HasAdminRightsUC;
use App\UseCases\Auth\LoginUC;
use App\UseCases\Auth\LogoutUC;
use App\UseCases\Auth\SignUpUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
	public function __construct(
		readonly private GoogleLoginUC $googleLoginUC,
		readonly private LoginUC $loginUC,
		readonly private SignUpUC $signUpUC,
		readonly private LogoutUC $logoutUC,
		readonly private CurrentUserUC $currentUserUC,
		readonly private HasAdminRightsUC $hasAdminRightsUC,
		readonly private UserService $userService,
	) {}

	public function signUp(SignupRequest $request): JsonResponse
	{
		$data = [
			'email' => $request->get('email'),
			'name' => $request->get('name'),
			'phone' => $request->get('phone'),
			'password' => $request->get('password'),
			'user_agent' => $request->get('user_agent'),
			'default_locale' => $this->userService->resolveUserDefaultLocale($request->get('language') ?? ''),
		];

		$user = $this->signUpUC->run($data);

		$userAndRoles = $this->loginUC->run($data);

		return HttpJson::OK($userAndRoles->wrapped('user'));
	}


	public function login(LoginRequest $request): JsonResponse
	{
		$data = [
			'email' => $request->get('email'),
			'password' => $request->get('password'),
			'user_agent' => $request->get('user_agent'),
		];

		$userAndRoles = $this->loginUC->run($data);

		if (!$userAndRoles) {
			return HttpJson::KO('invalid_credentials');
		}

		return HttpJson::OK($userAndRoles->wrapped('user'));
	}


	public function googleLogin(GoogleLoginRequest $request): JsonResponse
	{
		$data = [
			'token' => $request->get('token'),
			'user_agent' => $request->get('user_agent'),
			'default_locale' => $this->userService->resolveUserDefaultLocale($request->get('language') ?? ''),
		];
		$user = $this->googleLoginUC->run($data);
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

	public function hasAdminRights(int $userId): JsonResponse
	{
		$hasAdminRights = $this->hasAdminRightsUC->run([
			'id' => $userId,
		]);

		return HttpJson::OK(['has_admin_rights' => $hasAdminRights]);
	}

	public function hello(): JsonResponse
	{
		return HttpJson::OK('hello');
	}
}
