<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\SendPasswordResetRequest;
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

//	private function createVerificationToken(User $user): string
//	{
//		$email = $user->email;
//		$name = $user->name;
//		$oldPassword = $user->password;
//		$now = Carbon::now();
//		$expirationDate = $now->addDays(self::$RESET_GRACE_DAYS);
//		$tokenValue = implode(';', [$email, $name, $oldPassword]);
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
//		$oldPassword = $user->password;
//
//		return $token['email'] == $email && $token['name'] == $name && $token['old_password'] == $oldPassword;
//	}
//
//	private function parseVerificationToken(string $encryptedToken): array
//	{
//		$token = json_decode(Crypt::decrypt($encryptedToken));
//		$tokenValue = explode(';', Crypt::decrypt($token->value));
//		$expiration_date = Carbon::parse($token->expiration_date);
//		$email = $tokenValue[0];
//		$name = $tokenValue[1];
//		$old_password = $tokenValue[2];
//
//		return [
//			'email' => $email,
//			'name' => $name,
//			'old_password' => $old_password,
//			'expiration_date' => $expiration_date
//		];
//	}
}
