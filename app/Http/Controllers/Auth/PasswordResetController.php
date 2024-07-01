<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\SendPasswordResetRequest;
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
	static private int $VERIFICATION_GRACE_DAYS = 1;

	public function __construct(ResendService $mailService)
	{
		$this->mailService = $mailService;
	}

	public function resetPassword(PasswordResetRequest $request): JsonResponse
	{
		$email = $request->input('email');
		$token = $request->input('token');
		$newPassword = $request->input('new_password');
		$user = User::where('email', $email)->firstOrFail();

		if(!$this->isValidVerificationToken($user, $token)){
			return HttpJson::OK(
				'invalid token',
				Response::HTTP_UNAUTHORIZED
			);
		}

		$user->password = Hash::make($newPassword);
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
		$verificationToken = $this->createVerificationToken($user);
		$this->mailService->send($email, 'Email Verification', $verificationToken);

		return HttpJson::OK(
			'test send: ' . $verificationToken,
			Response::HTTP_CREATED
		);
	}

	private function createVerificationToken(User $user): string
	{
		$email = $user->email;
		$name = $user->name;
		$oldPassword = $user->password;
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$VERIFICATION_GRACE_DAYS);
		$token = [
			'value' => Crypt::encrypt($email . $name . $oldPassword),
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
		$oldPassword = $user->password;

		return $token['value'] == $email . $name . $oldPassword;
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
