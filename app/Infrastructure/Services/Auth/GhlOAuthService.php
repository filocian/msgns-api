<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Auth;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class GhlOAuthService
{
	public function __construct() {}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function getAccessToken(string $access_code): string
	{
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
		}
		return $response->body(); // Muestra el contenido en caso de error
	}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function refreshAccessToken(): string
	{
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
		}
		return $response->body(); // Muestra el contenido en caso de error
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
