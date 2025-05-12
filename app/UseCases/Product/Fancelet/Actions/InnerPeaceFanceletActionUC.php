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

		$html = view('emails.contact-fancelet-creator')
			->with([
				'fanceletTitle' => 'Yoga',
				'from' => $this->authService->userEmail(),
				'message' => $comment,
			])->render();

		$this->resendService->send(env('FANCELET_IP_CREATOR_MAIL'), 'INNER PEACE FANCELET MESSAGE', $html,);
	}
}
