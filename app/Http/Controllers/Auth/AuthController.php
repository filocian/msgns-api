<?php

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignupRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function signUp(SignupRequest $request): JsonResponse
    {
        $user = $this->authService->signUp(
            $request->get('email'),
            $request->get('name'),
            $request->get('password'),
        );

        return HttpJson::OK(
            ['user' => $user],
            Response::HTTP_CREATED
        );
    }


    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(
            $request->get('email'),
            $request->get('password')
        );

        return HttpJson::OK(['user' => $user]);
    }

    final public function logout(Request $request): JsonResponse
    {
        $bye = $this->authService->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return HttpJson::OK($bye);
    }

    public function currentUser(Request $request): JsonResponse
    {
        return HttpJson::OK($request->user());
    }

    public function hello(): JsonResponse
    {
        return HttpJson::OK('hello');
    }
}
