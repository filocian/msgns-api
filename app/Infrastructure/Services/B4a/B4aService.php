<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\B4a;

use App\Infrastructure\Repositories\B4a\B4aRepository;
use App\Models\Product;
use Carbon\Carbon;
use Exception;

final readonly class B4aService
{
	public function __construct(private B4aRepository $b4aRepository) {}

	/**
	 * @throws Exception
	 */
	public function getServerHealth(): bool
	{
		return $this->b4aRepository->getServerHealth();
	}

	/**
	 * @throws Exception
	 */
	public function createProductUsageObject(Product $product): \Parse\ParseObject
	{
		$object = $this->b4aRepository->createObject('ProductUsage');
		$object->set('productId', $product->id);
		$object->set('userId', $product->user_id);
		$object->set('usedAt', Carbon::now()->toDateTime());

		return $object;
	}
}
