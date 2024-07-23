<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\SendPasswordResetRequest;
use App\Http\Requests\Auth\SetUserPasswordRequest;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Mail\ResendService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

final class PasswordResetController extends Controller
{
	private ResendService $mailService;
	private AuthService $authService;

	public function __construct(ResendService $mailService, AuthService $authService)
	{
		$this->mailService = $mailService;
		$this->authService = $authService;
	}

	public function resetPassword(PasswordResetRequest $request): JsonResponse
	{
		$token = $request->input('token');
		$newPassword = $request->input('password');
		$parsedToken = $this->authService->parsePasswordResetToken($token);
		$user = User::where('email', $parsedToken['email'])->firstOrFail();

		if(!$this->authService->isValidPasswordResetToken($user, $token)){
			return HttpJson::OK(
				'invalid token',
				Response::HTTP_UNAUTHORIZED
			);
		}

		$user->password = Hash::make($newPassword);

		if($user->password_reset_required){
			$user->password_reset_required = false;
		}

		$user->save();
		$user->refresh();

		return HttpJson::OK(
			'good to go',
			Response::HTTP_CREATED
		);
	}

	public function sendPassResetEmail(SendPasswordResetRequest $request): JsonResponse
	{
		$email = $request->input('email');
		$user = User::where('email', $email)->firstOrFail();
		app()->setLocale($user->default_locale);
		$verificationToken = $this->authService->generatePasswordResetToken($user);
		$html = view('emails.reset-password')
			->with('email', $email)
			->with('verificationToken', $verificationToken)
			->render();
		$this->mailService->send($email, __('passwordReset.subject'), $html);

		return HttpJson::OK(
			'test send: ' . $verificationToken,
			Response::HTTP_CREATED
		);
	}

	public function setUserPassword(SetUserPasswordRequest $request, int $id): JsonResponse
	{
		$password = $request->input('password');
		$user = $this->authService->setUserPassword($id, $password);

		return HttpJson::OK(
			$user->wrapped('user'),
			Response::HTTP_CREATED
		);
	}
}
