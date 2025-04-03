<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\Pairing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Fancelet\FanceletContentDto;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class AnonymousFanceletPairingUC implements UseCaseContract
{
	public function __construct(private FanceletService $fanceletService) {}
	public function run(mixed $data = null, ?array $opts = null)
	{
		dd($data);
	}
}
