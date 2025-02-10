<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Infrastructure\Services\Auth\GhlOAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GoHighLevelOAuthController extends Controller
{
	public function __construct(
		private readonly GhlOAuthService $ghlOAuthService,
	) {}

	/**
	 * @throws ConnectionException
	 */
	public function authCode(Request $request): JsonResponse
	{
		$data = $request->get('code');

		$response = $this->ghlOAuthService->getAccessToken($data);

		return HttpJson::OK($response);
	}
}
