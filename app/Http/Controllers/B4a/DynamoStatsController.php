<?php

declare(strict_types=1);

namespace App\Http\Controllers\B4a;

use App\Http\Contracts\HttpJson;
use App\UseCases\DynamoDb\IntervalStatsUC;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

final class DynamoStatsController extends Controller
{
	public function __construct(
		private readonly IntervalStatsUC $intervalStatsUC
	) {}

	public function getServerHealth(): \Illuminate\Http\JsonResponse
	{
		$health = $this->intervalStatsUC->run();
		return HttpJson::OK(['alive' => $health]);
	}

	public function getLastMonthProductStats(Request $request, int $productId)
	{
		$timezone = $request->input('timezone') ?? 'UTC';
		$startOfPreviousMonth = Carbon::now($timezone)->subMonthNoOverflow()->startOfMonth()->toDateTimeString();
		$endOfPreviousMonth = Carbon::now($timezone)->subMonthNoOverflow()->endOfMonth()->toDateTimeString();

		dd($this->intervalStatsUC->run([
			'product_id' => $productId,
			'from' => $startOfPreviousMonth,
			'to' => $endOfPreviousMonth,
			'timezone' => $timezone,
		]));
	}

	public function getCurrentMonthProductStats(Request $request, int $productId)
	{
		$timezone = $request->input('timezone') ?? 'UTC';
		$startOfCurrentMonth = Carbon::now($timezone)->startOfMonth()->toDateTimeString();
		$endOfCurrentMonth = Carbon::now($timezone)->endOfMonth()->toDateTimeString();

		dd($this->intervalStatsUC->run([
			'product_id' => $productId,
			'from' => $startOfCurrentMonth,
			'to' => $endOfCurrentMonth,
			'timezone' => $timezone,
		]));
	}
	public function getIntervalProductStats(Request $request, int $productId)
	{

	}

}
