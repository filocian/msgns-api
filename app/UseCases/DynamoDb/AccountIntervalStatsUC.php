<?php

declare(strict_types=1);

namespace App\UseCases\DynamoDb;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Stats\AccountStatsDto;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use Carbon\Carbon;
use Exception;

final readonly class AccountIntervalStatsUC implements UseCaseContract
{
	public function __construct(private DynamoDbService $dynamoDbService) {}

	/**
	 * @param array{user_id: int, from: Carbon, to: Carbon, timezone: string} $data
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): AccountStatsDto
	{
		$userId = $data['user_id'];
		$from = $data['from'];
		$to = $data['to'];
		$timezone = $data['timezone'];

		return $this->dynamoDbService->getAccountUsageForGivenInterval($userId, $from, $to, $timezone);
	}
}
