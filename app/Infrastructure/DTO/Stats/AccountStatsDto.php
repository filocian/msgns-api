<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Stats;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Models\Product;
use Carbon\Carbon;

final class AccountStatsDto extends BaseDTO
{
	public int $user_id;
	public array $uses_by_product;
	public Carbon $from;
	public Carbon $to;
	public int $total_uses;
	public int $total_interval_uses;


	/**
	 * @param array{userId: int, items: array, from: Carbon, to: Carbon, timezone: string} $dynamoResponseModel
	 */
	public function __construct(array $dynamoResponseModel)
	{
		$this->user_id = $dynamoResponseModel['userId'];
		$this->from = $dynamoResponseModel['from'];
		$this->to = $dynamoResponseModel['to'];
		$this->total_uses = 0;
		$this->total_interval_uses = 0;

		$this->uses_by_product = [];

		$timezone = $dynamoResponseModel['timezone'];
		$userId = $this->user_id;
		$userProducts = Product::findProductsByUserId($userId);
		$items = collect($dynamoResponseModel['items']);


		foreach ($userProducts as $product) {
			$productScans = [];
			$productUsage = $items->filter(function ($item) use ($product) {
				return (int) $item['productId']['N'] === (int) $product->id;
			});
			$productUsage->each(function ($item) use (&$productScans, $timezone) {
				$productScans[] = denormalizeCarbonInstance($item['scannedAt']['S'], $timezone);
				;
			});

			$this->uses_by_product[] = [
				'id' => $product->id,
				'name' => $product->name,
				'model' => $product->model,
				'all_time_usage' => $product->usage,
				'interval_usage' => $productScans,
			];

			$this->total_interval_uses += $productUsage->count();
			$this->total_uses += $product->usage;
		}
	}
}
