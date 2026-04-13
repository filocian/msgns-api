<?php

declare(strict_types=1);

return [
	App\Providers\AppServiceProvider::class,
	Src\Shared\Infrastructure\Providers\BusServiceProvider::class,
	Src\Shared\Infrastructure\Providers\CacheServiceProvider::class,
	Src\Shared\Infrastructure\Providers\SharedServiceProvider::class,
	Src\Identity\Infrastructure\Providers\IdentityServiceProvider::class,
	Src\Places\Infrastructure\Providers\PlacesServiceProvider::class,
	Src\Products\Infrastructure\Providers\ProductsServiceProvider::class,
	Src\Subscriptions\Infrastructure\Providers\SubscriptionsServiceProvider::class,
	Src\Ai\Infrastructure\Providers\AiServiceProvider::class,
	Src\Billing\Infrastructure\Providers\BillingServiceProvider::class,
	Src\GoogleBusiness\Infrastructure\Providers\GoogleBusinessServiceProvider::class,
];
