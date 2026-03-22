<?php

declare(strict_types=1);

return [
	App\Providers\AppServiceProvider::class,
	Src\Shared\Infrastructure\Providers\BusServiceProvider::class,
	Src\Shared\Infrastructure\Providers\CacheServiceProvider::class,
	Src\Shared\Infrastructure\Providers\SharedServiceProvider::class,
	Src\Identity\Infrastructure\Providers\IdentityServiceProvider::class,
];
