<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\SetEmailVerifiedRequest;
use App\Http\Requests\Auth\SendEmailVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Mail\ResendService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class VerificationController extends Controller
{
	private ResendService $mailService;
	private AuthService $authService;

	public function __construct(ResendService $mailService, AuthService $authService)
	{
		$this->mailService = $mailService;
		$this->authService = $authService;
	}

	public function verify(VerifyEmailRequest $request): JsonResponse
	{
		$token = $request->input('token');
		$parsedToken = $this->authService->parseEmailVerificationToken($token);

		$user = User::where('email', $parsedToken['email'])->firstOrFail();

		if ($user->hasVerifiedEmail()) {
			return HttpJson::KO(
				'already_verified',
				Response::HTTP_BAD_REQUEST
			);
		}

		if (!$this->authService->isValidEmailVerificationToken($user, $token)) {
			return HttpJson::KO(
				'invalid_token',
				Response::HTTP_BAD_REQUEST
			);
		}

		$userDto = $this->authService->setEmailVerified($user->id);

		return HttpJson::OK(
			$userDto->wrapped('user'),
			Response::HTTP_CREATED
		);
	}

	public function sendVerificationEmail(SendEmailVerificationRequest $request): JsonResponse
	{
		$email = $request->input('email');
		$user = User::where('email', $email)->firstOrFail();

		if ($user->hasVerifiedEmail()) {
			return HttpJson::KO(
				'already_verified',
				Response::HTTP_BAD_REQUEST
			);
		}

		app()->setLocale($user->default_locale);
		$verificationToken = $this->authService->generateEmailVerificationToken($user);
		$html = view('emails.email-verification')->with('verificationToken', $verificationToken)->render();

		try{
			$this->mailService->send($email, __('emailVerification.subject'), $html);
		} catch (\Exception $error){
			return HttpJson::KO(
				$error->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		return HttpJson::OK(
			$verificationToken,
			Response::HTTP_CREATED
		);
	}

	public function setEmailVerified(SetEmailVerifiedRequest $request, int $id): JsonResponse
	{
		$userDto = $this->authService->setEmailVerified($id);

		return HttpJson::OK(
			$userDto->wrapped('user'),
			Response::HTTP_CREATED
		);
	}
}
