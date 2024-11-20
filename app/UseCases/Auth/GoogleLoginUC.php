<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\User;
use Illuminate\Validation\UnauthorizedException;
use Random\RandomException;

final readonly class GoogleLoginUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
		private MPLogger $mpLogger,
	) {}

	/**
	 * @param array{token: string, user_agent: string, default_locale: string} $data: Google credential JWT token
	 * @param array|null $opts
	 * @return UserDto
	 * @throws RandomException
	 */
	public function run(mixed $data = null, ?array $opts = null): UserDto
	{
		$token = $data['token'];
		$user_agent = $data['user_agent'];
		$user_language = $data['default_locale'];
		$googleUser = $this->extractDataFromGoogleToken($token);

		if (!$googleUser['google_id']) {
			$this->mpLogger->critical('LOGIN', 'GOOGLE LOGIN', 'Invalid google user id', [
				'user_data' => $googleUser,
			]);

			throw new UnauthorizedException('Invalid google user id');
		}

		$user = User::findByGoogleId($googleUser['google_id'], $googleUser['email']);

		if (!$user) {
			$userDto = $this->signup([
				'token' => $token,
				'default_locale' => $user_language,
				'user_agent' => $user_agent,
			]);
			$user = User::where('id', $userDto->id)->firstOrFail();
		}

		//TODO: Revisar amb Luis
		if (!isset($user->google_id)) {
			$user->update($user->id, [
				'google_id' => $googleUser['google_id'],
			]);
			$user->refresh();
		}

		if (!$user->hasVerifiedEmail()) {
			$user->markEmailAsVerified();
		}

		$this->authService->autoLogin($user, $user_agent);

		$this->mpLogger->critical('LOGIN', 'GOOGLE LOGIN', 'Logged In', [
			'user_data' => $user,
		]);

		return UserDto::fromModel($user);
	}

	/**
	 * @param array{token: string, user_agent: string, default_locale: string} $data: Google credential JWT token
	 * @return UserDto
	 * @throws RandomException
	 */
	private function signup(mixed $data): UserDto
	{
		$token = $data['token'];
		$defaultLocale = $data['default_locale'];
		$userAgent = $data['user_agent'];
		$googleUser = $this->extractDataFromGoogleToken($token);

		if (!$googleUser['google_id']) {
			throw new UnauthorizedException('Invalid google user id');
		}

		return UserDto::fromModel($this->authService->signUp([
			...$googleUser,
			'default_locale' => $defaultLocale,
			'user_agent' => $userAgent,
			'password' => bin2hex(random_bytes(10)),
		]));
	}


	/**
	 * @param string $token
	 * @return array{email: string, name: string, google_id: string}
	 */
	private function extractDataFromGoogleToken(string $token): array
	{
		list($headerPart, $payloadPart, $signaturePart) = explode('.', $token);

		$payload = json_decode(base64_decode($payloadPart), true);

		return [
			'email' => $payload['email'],
			'name' => $payload['name'],
			'google_id' => $payload['sub'],
		];
	}
}
