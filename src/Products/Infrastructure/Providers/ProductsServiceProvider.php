<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Products\Application\Commands\CreateProductType\CreateProductTypeHandler;
use Src\Products\Application\Commands\UpdateProductType\UpdateProductTypeHandler;
use Src\Products\Application\Queries\GetProductType\GetProductTypeHandler;
use Src\Products\Application\Queries\ListProductTypes\ListProductTypesHandler;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductTypeUsagePort;
use Src\Products\Infrastructure\Persistence\EloquentProductTypeRepository;
use Src\Products\Infrastructure\Persistence\EloquentProductTypeUsageAdapter;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class ProductsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductTypeRepository::class, EloquentProductTypeRepository::class);
        $this->app->bind(ProductTypeUsagePort::class, EloquentProductTypeUsageAdapter::class);
    }

    public function boot(): void
    {
        // Register command handlers
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('products.create_product_type', CreateProductTypeHandler::class);
        $commandBus->register('products.update_product_type', UpdateProductTypeHandler::class);

        // Register query handlers
        $queryBus = $this->app->make(QueryBus::class);
        $queryBus->register('products.list_product_types', ListProductTypesHandler::class);
        $queryBus->register('products.get_product_type', GetProductTypeHandler::class);

        // Load routes
        Route::prefix('api/v2/products')
            ->middleware('api')
            ->group(base_path('routes/api/products.php'));
    }
}
