<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Auth;

use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Factory\SocialLoginFactory;
use App\Infrastructure\Services\Mail\ResendService;
use App\Infrastructure\Services\MixPanel\MPLogger;
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
	private MPLogger $mpLogger;

	public function __construct(ResendService $mailService, UserService $userService, MPLogger $mpLogger)
	{
		$this->mailService = $mailService;
		$this->userService = $userService;
		$this->mpLogger = $mpLogger;
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
			$this->mpLogger->critical('USER_SIGNUP', 'USER SIGNUP', 'user signup failed', [
				'user_email' => $data['email'],
			]);

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
				$this->mpLogger->critical('USER_SIGNUP', 'USER SIGNUP EMAIL VERIFY', 'user signup email verification send failed', [
					'user_email' => $data['email'],
					'exception_message' => $error->getMessage(),
				]);

				throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,);
			}
		}

		$this->mpLogger->info('USER_SIGNUP', 'USER SIGNUP', 'user signe up successfully', [
			'user_email' => $data['email'],
		]);

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
			$this->mpLogger->critical('LOGIN', 'USER LOGIN ERROR', 'no user found', [
				'email' => $email,
			]);

			return null;
		}

		if ($user->password_reset_required) {
			$this->mpLogger->warn('LOGIN', 'PASSWORD RESET REQUIRED', 'user requires pass reset', [
				'email' => $email,
			]);

			return UserDto::fromModel($user);
		}

		if (!Auth::attempt(['email' => $email, 'password' => $password])) {
			$this->mpLogger->critical('LOGIN', 'USER LOGIN ERROR', 'invalid credentials', [
				'email' => $email,
			]);

			return null;
		}

		Request::session()->regenerate();

		$userRoles = $this->getRoles($user);

		if ($userRoles->count() < 1) {
			$this->setDefaultRole($user);
		}

		$this->userService->updateUserAgent($user->id, $user_agent);
		$this->userService->updateUserLastAccess($user->id);

		$this->mpLogger->info('LOGIN', 'USER LOGGED IN', 'user logged in successfully', [
			'email' => $email,
			'user_id' => $user->id,
		]);

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
		$userDto = UserDto::fromModel($user);
		$email = $userDto->email;
		$created_at = $userDto->created_at->toDateTimeString();
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$VERIFICATION_GRACE_DAYS);
		$tokenValue = implode(';', [$email, $created_at]);
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

		$userDto = UserDto::fromModel($user);
		$email = $userDto->email;
		$created_at = $userDto->created_at->toDateTimeString();

		return $token['email'] === $email && $token['created_at'] === $created_at;
	}

	public function parseEmailVerificationToken(string $encryptedToken): array
	{
		$token = json_decode(Crypt::decrypt($encryptedToken));
		$tokenValue = explode(';', Crypt::decrypt($token->value));
		$expiration_date = Carbon::parse($token->expiration_date);
		$email = $tokenValue[0];
		$created_at = $tokenValue[1];

		return [
			'email' => $email,
			'created_at' => $created_at,
			'expiration_date' => $expiration_date,
		];
	}

	public function generatePasswordResetToken(User $user): string
	{
		$userDto = UserDto::fromModel($user);
		$email = $userDto->email;
		$created_at = $userDto->created_at->toDateTimeString();
		$now = Carbon::now();
		$expirationDate = $now->addDays(self::$RESET_GRACE_DAYS);
		$tokenValue = implode(';', [$email, $created_at]);
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

		$userDto = UserDto::fromModel($user);
		$email = $userDto->email;
		$created_at = $userDto->created_at->toDateTimeString();

		return $token['email'] === $email && $token['created_at'] === $created_at;
	}

	public function parsePasswordResetToken(string $encryptedToken): array
	{
		$token = json_decode(Crypt::decrypt($encryptedToken));
		$tokenValue = explode(';', Crypt::decrypt($token->value));
		$expiration_date = Carbon::parse($token->expiration_date);
		$email = $tokenValue[0];
		$created_at = $tokenValue[1];

		return [
			'email' => $email,
			'created_at' => $created_at,
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
