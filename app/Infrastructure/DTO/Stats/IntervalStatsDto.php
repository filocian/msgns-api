<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Stats;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;

final class IntervalStatsDto extends BaseDTO
{
	public int $product_id;
	public array $uses;
	public Carbon $from;
	public Carbon $to;
	public int $totalUses;


	/**
	 * @param array{productId: int, userId: int, scannedAt: string[], from: Carbon, to: Carbon} $dynamoResponseModel
	 */
	public function __construct(array $dynamoResponseModel)
	{
		$this->product_id = $dynamoResponseModel['productId'];
		$this->from = $dynamoResponseModel['from'];
		$this->to = $dynamoResponseModel['to'];
		;
		$this->totalUses = count($dynamoResponseModel['scannedAt']);
		$this->uses = [];

		foreach ($dynamoResponseModel['scannedAt'] as $use) {
			$this->uses[] = $use;
		}
	}
}
