<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\B4a;

use Exception;
use Illuminate\Support\Facades\Log;
use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseUser;

final class B4aRepository
{
	private ParseUser|null $user;
	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		ParseClient::initialize(env('B4A_APP_ID'), env('B4A_REST_KEY'), env('B4A_MASTER_KEY'));
		ParseClient::setServerURL(env('B4A_SERVER_URL'), env('B4A_MOUNT_PATH'));

		try {
			$this->user = ParseUser::logIn(env('B4A_API_USER'), env('B4A_API_USER_PASSWORD'));
		} catch (ParseException $error) {
			Log::error($error);
			$this->user = null;
			throw new Exception('Failed to connect with B4A');
		}
		Log::info('B4A Repo initialized');
	}

	/**
	 * @throws Exception
	 */
	public function getServerHealth(): bool
	{
		return $this->user !== null;
	}

	public function createObject(string $className): ParseObject
	{
		return ParseObject::create($className);
	}
}
