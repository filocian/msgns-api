<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\Actions;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Mail\ResendService;

final readonly class InnerPeaceFanceletActionUC implements UseCaseContract
{
	public function __construct(private ResendService $resendService, private AuthService $authService) {}

	public function run(mixed $data = null, ?array $opts = null)
	{
		$productId = $data['product_id'];
		$productPassword = $data['product_password'];
		$comment = $data['message'];

		$body = 'FROM: ' . $this->authService->userEmail() . "\r\n";
		$body .= "MESSAGE: \r\n" . $comment . "\r\n";

		$this->resendService->send(env('FANCELET_IP_CREATOR_MAIL'), 'INNER PEACE FANCELET MESSAGE', $body,);
	}
}
