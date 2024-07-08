<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Auth;

use App\Infrastructure\DTO\RoleDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Factory\SocialLoginFactory;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AuthService
{
	/**
	 * @param array $data {email: string, name: string, password: string, google_id: ?string }
	 * @return UserDto
	 */
	public function signUp(array $data): UserDto
	{
		$user = User::create([
			'name' => $data['name'],
			'email' => $data['email'],
			'password' => bcrypt($data['password']),
			'google_id' => $data['google_id'] ?? '',
		]);

		if (!$user) {
			throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,);
		}

		$user->assignRole(StaticRoles::USER_ROLE);
		return UserDto::fromModel($user);
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @return array {user: UserDto, roles: array}
	 * @throws AuthenticationException
	 */
	public function login(string $email, string $password): array
	{
		$user = User::query()->where('email', $email)->first();

		if (!$user) {
			throw new UnauthorizedException();
		}

		if($user->password_reset_required){
			return [
				'user' => UserDto::fromModel($user),
			];
		}

		if (!Auth::attempt(['email' => $email, 'password' => $password])) {
			throw new AuthenticationException();
		}

		Request::session()->regenerate();

		$userRoles = $this->getRoles($user);
		$rolesDto = $userRoles->map(fn($role) => RoleDto::fromModel($role));

		return [
			'user' => UserDto::fromModel($user),
			'roles' => $rolesDto
		];
	}

	/**
	 * @param string $provider
	 * @param array $data {google_id: string, email: string, name: string}
	 * @return UserDto
	 */
	public function socialLogin(string $provider, array $data): UserDto|null
	{
		$socialLoginHandler = SocialLoginFactory::resolveProviderHandler($provider);
		return $socialLoginHandler->login($data);
	}

	/**
	 * @param string $provider
	 * @param array $data
	 * @return UserDto
	 */
	public function socialSignup(string $provider, array $data): UserDto
	{
		$socialLoginHandler = SocialLoginFactory::resolveProviderHandler($provider);
		return $socialLoginHandler->signup($data);
	}

	public function autoLogin(User|UserDto $user): void
	{
		if ($user instanceof UserDto) {
			$userId = $user->id;
			$user = new User();
			$user->id = $userId;
		}

		Auth::login($user);
	}


	public function logout(): bool
	{
		Auth::guard('stateful-api')->logout();
		Request::session()->invalidate();
		//		Request::session()->regenerateToken();

		return true;
	}

	public function user()
	{
		return Auth::user();
	}

	public function id(): ?int
	{
		if (!$this->user()) {
			return null;
		}
		return $this->user()->getAuthIdentifier();
	}

	public function isAuthenticated(): bool
	{
		return Auth::check();
	}

	private function buildUserToken(User $user): string
	{
		return $user->createToken(sprintf('%s-%s', $user->email, time()))->plainTextToken;
	}

	public function getRoles(User $user): Collection
	{
		$user ??= $this->user();

		if (!$user) {
			return [];
		}
		return $user->roles()->get();
	}
}
