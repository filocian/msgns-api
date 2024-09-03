<?php

declare(strict_types=1);

namespace App\Http\Controllers\B4a;

use App\Http\Contracts\HttpJson;
use App\UseCases\B4a\ServerHealthUC;
use Illuminate\Routing\Controller;

final class B4aController extends Controller
{
	public function __construct(private readonly ServerHealthUC $serverHealthUC) {}

	public function getServerHealth(): \Illuminate\Http\JsonResponse
	{
		$health = $this->serverHealthUC->run();
		return HttpJson::OK(['alive' => $health]);
	}
}
