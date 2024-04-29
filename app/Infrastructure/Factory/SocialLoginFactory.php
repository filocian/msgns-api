<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Services\Auth\GoogleLoginService;
use Illuminate\Support\Facades\App;

final class SocialLoginFactory
{
	public static function resolveProviderHandler(string $provider)
	{
		return match ($provider) {
			'google' => App::make(GoogleLoginService::class),
			default => null
		};
	}
}
