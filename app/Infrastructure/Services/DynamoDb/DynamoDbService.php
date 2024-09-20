<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\DynamoDb;

use App\Infrastructure\DTO\Stats\DailyStatsDto;
use App\Infrastructure\Repositories\DynamoDb\DynamoDbRepository;
use App\Models\Product;
use Carbon\Carbon;
use Exception;

final readonly class DynamoDbService
{
	public string $productUsageTable;
	public string $productConfigHistoryTable;

	public function __construct(private DynamoDbRepository $dynamoDbRepo)
	{
		$this->productUsageTable = config('services.dynamodb.product_usage_table');
		$this->productConfigHistoryTable = config('services.dynamodb.$product_config_history_table');
	}

	/**
	 * @throws Exception
	 */
	public function getServerHealth(): bool
	{
		return true;
	}

	public function test()
	{
		dd($this->dynamoDbRepo->listTables());
	}

	public function putProductUsage(Product $product, string $timestamp = null): void
	{
		$this->dynamoDbRepo->putItem($this->productUsageTable, [
			'productId' => ['N' => (string) $product->id],
			'userId' => ['N' => (string) $product->user_id],
			'scannedAt' => ['S' => $timestamp ?? Carbon::now()->toDateTimeString()],
			'productName' => ['S' => (string) $product->name],
		]);
	}

	public function getProductUsageForGivenInterval(int $productId, Carbon $startDate, Carbon $endDate, string $timezone)
	{
		$from = normalizeCarbonInstance($startDate)->toDateTimeString();
		$to = normalizeCarbonInstance($endDate)->toDateTimeString();

		$result = $this->dynamoDbRepo->query(
			$this->productUsageTable,
			'productId = :product_id AND #ts BETWEEN :start_ts AND :end_ts',
			[
				'#ts' => 'scannedAt',
			],
			[
				':product_id' => ['N' => (string) $productId],
				':start_ts' => ['S' => $from],
				':end_ts' => ['S' => $to],
			]
		);

		return new DailyStatsDto([
			'from' => denormalizeCarbonInstance($from, $timezone),
			'to' => denormalizeCarbonInstance($to, $timezone),
			'productId' => $productId,
			'scannedAt' => array_map(function ($item) use ($timezone) {
				return denormalizeCarbonInstance($item['scannedAt']['S'], $timezone);
			}, $result['Items']),
		]);
	}
}
