<?php

declare(strict_types=1);

namespace App\UseCases\B4a;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\B4a\B4aService;
use Exception;

final readonly class ServerHealthUC implements UseCaseContract
{
	public function __construct(private B4aService $b4aService) {}

	/**
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): bool
	{
		return $this->b4aService->getServerHealth();
	}
}
