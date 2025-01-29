<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\Actions;

use App\Infrastructure\Contracts\UseCaseContract;

final readonly class LoveFanceletActionUC implements UseCaseContract
{
	public function __construct(private FanceletMessageActionUC $actionUC) {}

	public function run(mixed $data = null, ?array $opts = null)
	{
		$productId = $data['product_id'];
		$productPassword = $data['product_password'];
		$comment = $data['message'];

		$this->actionUC->run([
			'product_id' => $productId,
			'product_password' => $productPassword,
			'message' => $comment,
			'target_table' => 'fancelet_actions_registry',
		]);
	}
}
