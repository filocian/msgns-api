<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use Illuminate\Validation\UnauthorizedException;
use Random\RandomException;

final readonly class GoogleLoginUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService
	) {}

	/**
	 * @param array{token: string, user_agent: mixed} $data: Google credential JWT token
	 * @param array|null $opts
	 * @return UserDto
	 * @throws RandomException
	 */
	public function run(mixed $data = null, ?array $opts = null): UserDto
	{
		$token = $data['token'];
		$user_agent = $data['user_agent'];
		$googleUser = $this->extractDataFromGoogleToken($token);

		if (!$googleUser['google_id']) {
			throw new UnauthorizedException('Invalid google user id');
		}

		$user = User::findByGoogleId($googleUser['google_id'], $googleUser['email']);

		if (!$user) {
			$userDto = $this->signup($token);
			$user = User::where('id', $userDto->id)->firstOrFail();
		}

		//TODO: Revisar amb Luis
		if (!isset($user->google_id)) {
			$user->update($user->id, [
				'google_id' => $googleUser['google_id'],
			]);
			$user->refresh();
		}

		if(!$user->hasVerifiedEmail()) {
			$user->markEmailAsVerified();
		}

		$this->authService->autoLogin($user, $user_agent);

		return UserDto::fromModel($user);
	}

	/**
	 * @param string $data
	 * @return UserDto
	 * @throws RandomException
	 */
	private function signup(mixed $data): UserDto
	{
		$googleUser = $this->extractDataFromGoogleToken($data);

		if (!$googleUser['google_id']) {
			throw new UnauthorizedException('Invalid google user id');
		}

		return UserDto::fromModel($this->authService->signUp([
			...$googleUser,
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
