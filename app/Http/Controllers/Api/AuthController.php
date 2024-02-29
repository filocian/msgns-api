<?php

namespace App\Http\Controllers\Api;

use App\Http\Contracts\HttpJson;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    final public function signUp(Request $request): JsonResponse
    {+
        try {,
            $this->signUpValidation($request);

            $user = $this->authService->signUp(
                $request->get('email'),
                $request->get('name'),
                $request->get('password'),
            );

            return HttpJson::OK(
                [ 'user' => $user ],
                Response::HTTP_CREATED
            );

        } catch (\Exception $e) {
            return HttpJson::KO($e->getMessage());
        }
    }


    final public function login(Request $request): JsonResponse
    {
        try {
            $this->loginValidation($request);

            $token = $this->authService->login(
                $request->get('email'),
                $request->get('password')
            );

            return HttpJson::OK( [ 'token' => $token ]);

        } catch (ValidationException $exception) {
            throw $exception;
        }

    }

    final public function logout(): JsonResponse
    {
        try {
            auth()->user()->tokens()->delete();

            return HttpJson::OK(true);

        } catch (\Exception $e) {
            return HttpJson::KO($e->getMessage());
        }
    }

    private function loginValidation(Request $request): array
    {
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    }

    private function signUpValidation(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string',
            'repeat_password' => 'required|string|same:password'
        ]);
    }
}
