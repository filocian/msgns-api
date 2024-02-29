<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public static function isDeferred() {
        return false;
    }

    public function signUp(string $email, string $name, string $password)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);

        if (!$user) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,);
        }

        return $user;
    }


    public function login(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $this->buildUserToken($user);

        return $token;
    }


    final public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    private function buildUserToken(User $user): string
    {
        return $user->createToken(sprintf('%s-%s', $user->email, time()))->plainTextToken;
    }
}
