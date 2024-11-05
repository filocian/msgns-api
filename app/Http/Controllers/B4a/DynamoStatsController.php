<?php

declare(strict_types=1);

namespace App\Http\Controllers\B4a;

use App\Http\Contracts\HttpJson;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Product;
use App\UseCases\DynamoDb\AccountIntervalStatsUC;
use App\UseCases\DynamoDb\IntervalStatsUC;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class DynamoStatsController extends Controller
{
	public function __construct(
		private readonly IntervalStatsUC $intervalStatsUC,
		private readonly AccountIntervalStatsUC $accountIntervalStatsUC,
		private readonly DynamoDbService $dynamoDbService
	) {}

	public function getServerHealth(): JsonResponse
	{
		$health = $this->intervalStatsUC->run();
		return HttpJson::OK(['alive' => $health]);
	}

	public function getLastMonthProductStats(Request $request, int $productId)
	{
		$timezone = $request->input('timezone') ?? 'UTC';
		$startOfPreviousMonth = Carbon::now($timezone)->subMonthNoOverflow()->startOfMonth();
		$endOfPreviousMonth = Carbon::now($timezone)->subMonthNoOverflow()->endOfMonth();

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
		$startOfCurrentMonth = Carbon::now($timezone)->startOfMonth();
		$endOfCurrentMonth = Carbon::now($timezone)->endOfMonth();

		dd($this->intervalStatsUC->run([
			'product_id' => $productId,
			'from' => $startOfCurrentMonth,
			'to' => $endOfCurrentMonth,
			'timezone' => $timezone,
		]));
	}

	public function getIntervalProductStats(Request $request, int $productId): JsonResponse
	{
		$timezone = $request->input('timezone') ?? 'UTC';
		$from = parseLocalizedDateTimeString($request->input('from'), $timezone);
		$to = parseLocalizedDateTimeString($request->input('to'), $timezone);

		$result = $this->intervalStatsUC->run([
			'product_id' => $productId,
			'from' => $from,
			'to' => $to,
			'timezone' => $timezone,
		]);

		return HttpJson::OK($result->wrapped('stats'));
	}

	public function getIntervalAccountStats(Request $request, int $userId): JsonResponse
	{
		$timezone = $request->input('timezone') ?? 'UTC';
		$from = parseLocalizedDateTimeString($request->input('from'), $timezone);
		$to = parseLocalizedDateTimeString($request->input('to'), $timezone);

		$result = $this->accountIntervalStatsUC->run([
			'user_id' => $userId,
			'from' => $from,
			'to' => $to,
			'timezone' => $timezone,
		]);

		return HttpJson::OK($result->wrapped('stats'));
	}

	public function seedTestData(int $productId)
	{
		$product = Product::findById($productId);
		$today = Carbon::now();
		$pastDay = $today->copy()->subDays(100);
		$marks = [];

		//last month
		for ($x = 1; $x <= 100; $x++) {
			$day = $pastDay->copy()->addDays($x);
			for ($y = 0; $y < 30; $y++) {
				$mark = $day->copy()->addHours(rand(1, 23))->format('Y-m-d H:i:s.u');
				$marks[] = $mark;
				$this->dynamoDbService->putProductUsage($product, $mark);
				usleep(1000);
			}
		}

		dd($marks);
	}
}
