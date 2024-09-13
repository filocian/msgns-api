<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Auth;

use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Factory\SocialLoginFactory;
use App\Infrastructure\Services\Mail\ResendService;
use App\Infrastructure\Services\User\UserService;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AuthService
{
	private static int $VERIFICATION_GRACE_DAYS = 3;
	private static int $RESET_GRACE_DAYS = 1;
	private static $ADMIN_ROLES = [StaticRoles::DEV_ROLE, StaticRoles::BACKOFFICE_ROLE];
	private ResendService $mailService;
	private UserService $userService;

	public function __construct(ResendService $mailService, UserService $userService)
	{
		$this->mailService = $mailService;
		$this->userService = $userService;
	}

	/**
	 * @param array $data {email: string, name: string, password: string, google_id: ?string }
	 * @return User
	 */
	public function signUp(array $data): User
	{
		$user = User::create([
			'name' => $data['name'],
			'email' => $data['email'],
			'phone' => $data['phone'] ?? null,
			'password' => bcrypt($data['password']),
			'google_id' => $data['google_id'] ?? '',
			'password_reset_required' => false,
			'default_locale' => $data['default_locale'],
			'user_agent' => $data['user_agent'] ?? null,
			'last_access' => Carbon::now(),
		]);

		if (!$user) {
			throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,);
		}

		$user->assignRole(StaticRoles::USER_ROLE);

		if ($user->google_id === '') {
			app()->setLocale($user->default_locale);
			$verificationToken = $this->generateEmailVerificationToken($user);
			$html = view('emails.email-verification')->with('verificationToken', $verificationToken)->render();

			try {
				$this->mailService->send($user->email, __('emailVerification.subject'), $html);
			} catch (Exception $error) {
				dd($error->getMessage());
			}
		}

		return $user;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string|null $user_agent
	 * @return UserDto | null
	 */
	public function login(string $email, string $password, string|null $user_agent): UserDto|null
	{
		$user = User::query()->where('email', $email)->first();

		if (!$user) {
			return null;
		}

		if ($user->password_reset_required) {
			return UserDto::fromModel($user);
		}

		if (!Auth::attempt(['email' => $email, 'password' => $password])) {
			return null;
		}

		Request::session()->regenerate();

		$userRoles = $this->getRoles($user);

		if ($userRoles->count() < 1) {
			$this->setDefaultRole($user);
		}

		$this->userService->updateUserAgent($user->id, $user_agent);
		$this->userService->updateUserLastAccess($user->id);

		return UserDto::fromModel($user);
	}

	/**
	 * @param string $provider
	 * @param array $data {google_id: string, email: string, name: string}
	 * @return UserDto|null
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

	public function autoLogin(User|UserDto $user, mixed $user_agent = null): void
	{
		if ($user instanceof UserDto) {
			$userId = $user->id;
			$user = new User();
			$user->id = $userId;
		}

		$userRoles = $this->getRoles($user);

		if ($userRoles->count() < 1) {
			$this->setDefaultRole($user);
		}

		$this->userService->updateUserLastAccess($user->id);
		$this->userService->updateUserAgent($user->id, $user_agent);



		Auth::login($user);
	}


	public function logout(): bool
	{
		Log::info(config('session.cookie'));
		Auth::guard('stateful-api')->logout();
		Cookie::queue(Cookie::forget(config('session.cookie')));
		Request::session()->invalidate();
		Request::session()->regenerateToken();

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

	public function getRoles(User $user): array|Collection
	{
		$user ??= $this->user();

		if (!$user) {
			return [];
		}
		return $user->roles()->get();
	}

	public function generateEmailVerificationToken(User $user): string
	{
		$email = $user->email;
		$name = $user->name;
		$created_at = $user->created_at;
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$VERIFICATION_GRACE_DAYS);
		$tokenValue = implode(';', [$email, $name, $created_at]);
		$token = [
			'value' => Crypt::encrypt($tokenValue),
			'expiration_date' => $expirationDate->toDateTimeString(),
		];

		return Crypt::encrypt(json_encode($token));
	}

	public function isValidEmailVerificationToken(User $user, string $encryptedToken): bool
	{
		$token = $this->parseEmailVerificationToken($encryptedToken);
		$now = Carbon::now();

		if (!$now->lessThanOrEqualTo($token['expiration_date'])) {
			return false;
		}

		$email = $user->email;
		$name = $user->name;
		$created_at = $user->created_at;

		return $token['email'] === $email && $token['name'] === $name && $token['created_at'] === $created_at;
	}

	public function parseEmailVerificationToken(string $encryptedToken): array
	{
		$token = json_decode(Crypt::decrypt($encryptedToken));
		$tokenValue = explode(';', Crypt::decrypt($token->value));
		$expiration_date = Carbon::parse($token->expiration_date);
		$email = $tokenValue[0];
		$name = $tokenValue[1];
		$created_at = $tokenValue[2];

		return [
			'email' => $email,
			'name' => $name,
			'created_at' => $created_at,
			'expiration_date' => $expiration_date,
		];
	}

	public function generatePasswordResetToken(User $user): string
	{
		$email = $user->email;
		$name = $user->name;
		$oldPassword = $user->password;
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$RESET_GRACE_DAYS);
		$tokenValue = implode(';', [$email, $name, $oldPassword]);
		$token = [
			'value' => Crypt::encrypt($tokenValue),
			'expiration_date' => $expirationDate->toDateTimeString(),
		];

		return Crypt::encrypt(json_encode($token));
	}

	public function isValidPasswordResetToken(User $user, string $encryptedToken): bool
	{
		$token = $this->parsePasswordResetToken($encryptedToken);
		$now = Carbon::now();

		if (!$now->lessThanOrEqualTo($token['expiration_date'])) {
			return false;
		}

		$email = $user->email;
		$name = $user->name;
		$oldPassword = $user->password;

		return $token['email'] === $email && $token['name'] === $name && $token['old_password'] === $oldPassword;
	}

	public function parsePasswordResetToken(string $encryptedToken): array
	{
		$token = json_decode(Crypt::decrypt($encryptedToken));
		$tokenValue = explode(';', Crypt::decrypt($token->value));
		$expiration_date = Carbon::parse($token->expiration_date);
		$email = $tokenValue[0];
		$name = $tokenValue[1];
		$old_password = $tokenValue[2];

		return [
			'email' => $email,
			'name' => $name,
			'old_password' => $old_password,
			'expiration_date' => $expiration_date,
		];
	}

	public function setEmailVerified(int $userId): UserDto
	{
		$user = User::where('id', $userId)->firstorFail();
		$user->markEmailAsVerified();

		return UserDto::fromModel($user);
	}

	public function setUserPassword(int $userId, string $password): UserDto
	{
		$user = User::where('id', $userId)->firstOrFail();
		$user->password = Hash::make($password);
		$user->password_reset_required = false;
		$user->save();
		$user->refresh();

		return UserDto::fromModel($user);
	}

	public function hasAdminRole(int $userId): bool
	{
		$user = User::where('id', $userId)->firstOrfail();

		return $user->hasAnyRole(self::$ADMIN_ROLES);
	}

	private function setDefaultRole(User $user): void
	{
		if ($role = Role::findByName(StaticRoles::USER_ROLE, 'stateful-api')) {
			$user->assignRole($role);
		}
	}
}
