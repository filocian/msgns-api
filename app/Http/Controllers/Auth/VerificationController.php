<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\SendEmailVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Mail\ResendService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

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
			return HttpJson::OK(
				'already verified',
				Response::HTTP_UNAUTHORIZED
			);
		}

		if (!$this->authService->isValidEmailVerificationToken($user, $token)) {
			return HttpJson::KO(
				'invalid token',
				Response::HTTP_UNAUTHORIZED
			);
		}

		$user->markEmailAsVerified();

		return HttpJson::OK(
			'good to go',
			Response::HTTP_CREATED
		);
	}

	public function sendVerificationEmail(SendEmailVerificationRequest $request): JsonResponse
	{
		$email = $request->input('email');
		$user = User::where('email', $email)->firstOrFail();

		if ($user->hasVerifiedEmail()) {
			return HttpJson::OK(
				'already verified',
				Response::HTTP_UNAUTHORIZED
			);
		}

		app()->setLocale($user->default_locale);
		$verificationToken = $this->authService->generateEmailVerificationToken($user);
		$html = view('emails.email-verification')->with('verificationToken', $verificationToken)->render();

		try{
			$this->mailService->send($email, __('emailVerification.subject'), $html);
		} catch (\Exception $error){
			return HttpJson::OK(
				$error->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		return HttpJson::OK(
			$verificationToken,
			Response::HTTP_CREATED
		);
	}

//	private function createVerificationToken(User $user): string
//	{
//		$email = $user->email;
//		$name = $user->name;
//		$created_at = $user->created_at;
//		$now = Carbon::now();
//		$expirationDate = $now->addDays(self::$VERIFICATION_GRACE_DAYS);
//		$tokenValue = implode(';', [$email, $name, $created_at]);
//		$token = [
//			'value' => Crypt::encrypt($tokenValue),
//			'expiration_date' => $expirationDate->toDateTimeString()
//		];
//
//		return Crypt::encrypt(json_encode($token));
//	}
//
//	private function isValidVerificationToken(User $user, string $encryptedToken): bool
//	{
//		$token = $this->parseVerificationToken($encryptedToken);
//		$now = Carbon::now();
//
//		if (!$now->lessThanOrEqualTo($token['expiration_date'])) {
//			return false;
//		}
//
//		$email = $user->email;
//		$name = $user->name;
//		$created_at = $user->created_at;
//
//		return $token['email'] == $email && $token['name'] == $name && $token['created_at'] == $created_at;
//	}
//
//	private function parseVerificationToken(string $encryptedToken): array
//	{
//		$token = json_decode(Crypt::decrypt($encryptedToken));
//		$tokenValue = explode(';', Crypt::decrypt($token->value));
//		$expiration_date = Carbon::parse($token->expiration_date);
//		$email = $tokenValue[0];
//		$name = $tokenValue[1];
//		$created_at = $tokenValue[2];
//
//		return [
//			'email' => $email,
//			'name' => $name,
//			'created_at' => $created_at,
//			'expiration_date' => $expiration_date
//		];
//	}
}
