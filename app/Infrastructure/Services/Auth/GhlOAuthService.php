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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class GhlOAuthService
{
	public function __construct(){}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function getAccessToken(string $access_code): string {
		$url = 'https://services.leadconnectorhq.com/oauth/token';
		$data = [
			'client_id' => '679904d3b09ca8492a46f0e6-m6i6znlo',
			'client_secret' => '07b9e851-efd9-4a4e-99b7-dcf076d59fb2',
			'grant_type' => 'authorization_code',
			'code' => $access_code,
		];
		$response = Http::asForm()->post($url, $data);

		if ($response->successful()) {
			$access_token = $response->json()['access_token'];
			$refresh_token = $response->json()['refresh_token'];
			$location_id = $response->json()['locationId'];

			try {
				$this->storeAccessToken($access_token, $refresh_token, $location_id);
			} catch (Exception $e) {
				throw new Exception($e->getMessage(), $e->getCode());
			}

			return 'ok';
		} else {
			return $response->body(); // Muestra el contenido en caso de error
		}
	}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function refreshAccessToken(): string {
		$url = 'https://services.leadconnectorhq.com/oauth/token';
		$refresh_token = $this->retrieveAccessToken()['refresh_token'];
		$data = [
			'client_id' => '679904d3b09ca8492a46f0e6-m6i6znlo',
			'client_secret' => '07b9e851-efd9-4a4e-99b7-dcf076d59fb2',
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
		];
		$response = Http::asForm()->post($url, $data);

		if ($response->successful()) {
			$access_token = $response->json()['access_token'];
			$refresh_token = $response->json()['refresh_token'];
			$location_id = $response->json()['locationId'];

			try {
				$this->storeAccessToken($access_token, $refresh_token, $location_id);
			} catch (Exception $e) {
				throw new Exception($e->getMessage(), $e->getCode());
			}

			return 'ok';
		} else {
			return $response->body(); // Muestra el contenido en caso de error
		}
	}

	/**
	 * @throws Exception
	 */
	private function storeAccessToken(string $access_token, string $refresh_token, string $location_id): void
	{
		try {
			DB::table('ghl_oauth')->updateOrInsert(['token_type' => 'access_token'], ['token' => $access_token]);
			DB::table('ghl_oauth')->updateOrInsert(['token_type' => 'refresh_token'], ['token' => $refresh_token]);
			DB::table('ghl_oauth')->updateOrInsert(['token_type' => 'location_id'], ['token' => $location_id]);
		} catch (Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	public function retrieveAccessToken(): array
	{
		$access_token = DB::table('ghl_oauth')->where(['token_type' => 'access_token'])->first()->token;
		$refresh_token = DB::table('ghl_oauth')->where(['token_type' => 'refresh_token'])->first()->token;
		$location_id = DB::table('ghl_oauth')->where(['token_type' => 'location_id'])->first()->token;

		return ['access_token' => $access_token, 'refresh_token' => $refresh_token, 'location_id' => $location_id];
	}
}
