<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
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

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        Request::session()->regenerate();

        $user = User::query()->where('email', $email)->first();

        return $user;
    }


    public function logout()
    {
        Auth::guard('web')->logout();

        return true;
    }

    public function user()
    {
        return Auth::user();
    }


    public function id(): int
    {
        return $this->user()->getAuthIdentifier();
    }

    private function buildUserToken(User $user): string
    {
        return $user->createToken(sprintf('%s-%s', $user->email, time()))->plainTextToken;
    }
}
