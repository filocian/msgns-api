<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\SendEmailVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Infrastructure\Services\Mail\ResendService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

final class VerificationController extends Controller
{
	private ResendService $mailService;
	static private int $VERIFICATION_GRACE_DAYS = 3;

	public function __construct(ResendService $mailService)
	{
		$this->mailService = $mailService;
	}

	public function verify(VerifyEmailRequest $request): JsonResponse
	{
		$email = $request->input('email');
		$token = $request->input('token');

		$user = User::where('email', $email)->firstOrFail();

		if ($user->hasVerifiedEmail()) {
			return HttpJson::OK(
				'already verified',
				Response::HTTP_UNAUTHORIZED
			);
		}

		if (!$this->isValidVerificationToken($user, $token)) {
			return HttpJson::OK(
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
		$verificationToken = $this->createVerificationToken($user);
		$html = view('emails.email-verification')->with('verificationToken', $verificationToken)->render();
		$this->mailService->send($email, __('emailVerification.subject'), $html);

		return HttpJson::OK(
			'test send: ' . $verificationToken,
			Response::HTTP_CREATED
		);
	}

	private function createVerificationToken(User $user): string
	{
		$email = $user->email;
		$name = $user->name;
		$created_at = $user->created_at;
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$VERIFICATION_GRACE_DAYS);
		$token = [
			'value' => Crypt::encrypt($email . $name . $created_at),
			'expiration_date' => $expirationDate->toDateTimeString()
		];

		return Crypt::encrypt(json_encode($token));
	}

	private function isValidVerificationToken(User $user, string $encryptedToken): bool
	{
		$token = $this->parseVerificationToken($encryptedToken);
		$now = Carbon::now();

		if (!$now->lessThanOrEqualTo($token['expiration_date'])) {
			return false;
		}

		$email = $user->email;
		$name = $user->name;
		$created_at = $user->created_at;

		return $token['value'] == $email . $name . $created_at;
	}

	private function parseVerificationToken(string $encryptedToken): array
	{
		$token = json_decode(Crypt::decrypt($encryptedToken));

		return [
			'value' => Crypt::decrypt($token->value),
			'expiration_date' => Carbon::parse($token->expiration_date)
		];
	}
}
