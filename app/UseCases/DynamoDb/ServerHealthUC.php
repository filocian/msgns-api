<?php

declare(strict_types=1);

namespace App\UseCases\DynamoDb;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use Exception;

final readonly class ServerHealthUC implements UseCaseContract
{
	public function __construct(private DynamoDbService $b4aService) {}

	/**
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): bool
	{
		return $this->b4aService->getServerHealth();
	}
}
