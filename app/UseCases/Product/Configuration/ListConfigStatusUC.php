<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductConfigStatusDto;
use App\Models\ProductConfigurationStatus;

final readonly class ListConfigStatusUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{id: int, name: string}|null $data
	 * @param array|null $opts
	 * @return CollectionDto

	 */
	public function run(mixed $data = null, ?array $opts = null): CollectionDto
	{
		$statusList = ProductConfigurationStatus::list();

		return CollectionDto::fromModelCollection($statusList, ProductConfigStatusDto::class);
	}
}
