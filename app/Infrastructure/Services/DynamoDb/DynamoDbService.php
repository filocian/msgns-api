<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\DynamoDb;

use App\Infrastructure\DTO\Fancelet\FanceletGroupCommentsDto;
use App\Infrastructure\DTO\Stats\AccountStatsDto;
use App\Infrastructure\DTO\Stats\IntervalStatsDto;
use App\Infrastructure\Repositories\DynamoDb\DynamoDbRepository;
use App\Models\Product;
use Carbon\Carbon;
use Exception;

final readonly class DynamoDbService
{
	public string $productUsageTable;
	public string $productConfigHistoryTable;
	public string $fanceletCommentsTable;

	public function __construct(private DynamoDbRepository $dynamoDbRepo)
	{
		$this->productUsageTable = config('services.dynamodb.product_usage_table');
		$this->fanceletCommentsTable = config('services.dynamodb.fancelet_comments_table');
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
			'scannedAt' => ['S' => $timestamp ?? Carbon::now()->format('Y-m-d H:i:s.u')],
			'productName' => ['S' => (string) $product->name],
		]);
	}

	public function getProductUsageForGivenInterval(
		int $productId,
		Carbon $startDate,
		Carbon $endDate,
		string $timezone
	): IntervalStatsDto {
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

		return new IntervalStatsDto([
			'from' => denormalizeCarbonInstance($from, $timezone),
			'to' => denormalizeCarbonInstance($to, $timezone),
			'productId' => $productId,
			'scannedAt' => array_map(function ($item) use ($timezone) {
				return denormalizeCarbonInstance($item['scannedAt']['S'], $timezone);
			}, $result['Items']),
		]);
	}

	public function getAccountUsageForGivenInterval(
		int $userId,
		Carbon $startDate,
		Carbon $endDate,
		string $timezone
	): AccountStatsDto {
		$from = normalizeCarbonInstance($startDate)->toDateTimeString();
		$to = normalizeCarbonInstance($endDate)->toDateTimeString();

		$result = $this->dynamoDbRepo->scan(
			$this->productUsageTable,
			'userId = :user_id AND #ts BETWEEN :start_ts AND :end_ts',
			[
				'#ts' => 'scannedAt',
			],
			[
				':user_id' => ['N' => (string) $userId],
				':start_ts' => ['S' => $from],
				':end_ts' => ['S' => $to],
			]
		);

		return new AccountStatsDto([
			'userId' => $userId,
			'from' => denormalizeCarbonInstance($from, $timezone),
			'to' => denormalizeCarbonInstance($to, $timezone),
			'items' => $result['Items'],
			'timezone' => $timezone,
		]);
	}

	public function deleteProductStats(int $productId): void
	{
		$tableName = $this->productUsageTable;
		$keyConditionExpression = 'productId = :product_id';
		$expressionAttributeValues = [
			':product_id' => ['N' => (string) $productId],
		];
		$keyNames = ['productId', 'scannedAt'];

		$this->dynamoDbRepo->batchDelete($tableName, $keyConditionExpression, $expressionAttributeValues, $keyNames);
	}

	public function addFanceletComment(int $productId, string $productGroup, string $comment): void
	{
		$this->dynamoDbRepo->putItem($this->fanceletCommentsTable, [
			'ProductId' => ['N' => (string) $productId],
			'FanceletGroup' => ['S' => $productGroup],
			'comment' => ['S' => $comment],
			'Timestamp' => ['S' => $timestamp ?? Carbon::now()->format('Y-m-d H:i:s.u')],
		]);
	}

	public function getFanceletGroupComments(string $groupId): FanceletGroupCommentsDto
	{
		$result = $this->dynamoDbRepo->query(
			$this->fanceletCommentsTable,
			'#group = :group_id',
			[
				'#group' => 'FanceletGroup',
			],
			[
				':group_id' => ['S' => $groupId],
			],
			'DESC'
		);

		return new FanceletGroupCommentsDto($groupId, $result['Items']);
	}
}
