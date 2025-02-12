<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\Comments;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class FanceletCommentUC implements UseCaseContract
{
	public function __construct(private FanceletService $fanceletService) {}

	/**
	 * @param array{product_id: int, product_password: string, message: string} $data
	 */
	public function run(mixed $data = null, ?array $opts = null): bool
	{
		$productId = (int) $data['product_id'];
		$productPassword = $data['product_password'];
		$message = $data['message'];
		$targetTable = 'fancelet_comments_registry';

		DB::transaction(function () use ($productId, $productPassword, $message, $targetTable) {
			$entry = DB::table($targetTable)->where('product_id', $productId)->first();

			if (!$entry) {
				DB::table($targetTable)->insert([
					'product_id' => $productId,
					'created_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
				]);
			}

			DB::table($targetTable)->where('product_id', $productId)->increment('usage');
			DB::table($targetTable)->where('product_id', $productId)
				->update(['updated_at' => Carbon::now()->format('Y-m-d H:i:s.u')]);
		});

		return $this->fanceletService->sendComment($productId, $productPassword, $message);
	}
}
