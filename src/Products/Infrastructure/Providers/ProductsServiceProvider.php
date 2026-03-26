<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Products\Application\Commands\CreateProductType\CreateProductTypeHandler;
use Src\Products\Application\Commands\ActivateProduct\ActivateProductHandler;
use Src\Products\Application\Commands\AssignToUser\AssignToUserHandler;
use Src\Products\Application\Commands\ChangeConfigStatus\ChangeConfigStatusHandler;
use Src\Products\Application\Commands\DeactivateProduct\DeactivateProductHandler;
use Src\Products\Application\Commands\GenerateProducts\GenerateProductsHandler;
use Src\Products\Application\Commands\RemoveProductLink\RemoveProductLinkHandler;
use Src\Products\Application\Commands\RenameProduct\RenameProductHandler;
use Src\Products\Application\Commands\ReportUsage\ReportUsageHandler;
use Src\Products\Application\Commands\RestoreProduct\RestoreProductHandler;
use Src\Products\Application\Commands\SetTargetUrl\SetTargetUrlHandler;
use Src\Products\Application\Commands\SoftRemoveProduct\SoftRemoveProductHandler;
use Src\Products\Application\Commands\UpdateProductType\UpdateProductTypeHandler;
use Src\Products\Application\Queries\GetProductType\GetProductTypeHandler;
use Src\Products\Application\Queries\ListProductTypes\ListProductTypesHandler;
use Src\Products\Domain\Ports\ExcelExportPort;
use Src\Products\Domain\Ports\PasswordGeneratorPort;
use Src\Products\Domain\Ports\ProductBusinessPort;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductTypeUsagePort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\Services\ProductGenerationService;
use Src\Products\Infrastructure\Persistence\DynamoDbProductUsageAdapter;
use Src\Products\Infrastructure\Persistence\EloquentProductBusinessRepository;
use Src\Products\Infrastructure\Persistence\EloquentProductRepository;
use Src\Products\Infrastructure\Persistence\EloquentProductTypeRepository;
use Src\Products\Infrastructure\Persistence\EloquentProductTypeUsageAdapter;
use Src\Products\Infrastructure\Services\AlphanumericPasswordGenerator;
use Src\Products\Infrastructure\Services\PhpSpreadsheetExcelExporter;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Core\Ports\NoSqlPort;

final class ProductsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ProductType bindings (existing)
        $this->app->bind(ProductTypeRepository::class, EloquentProductTypeRepository::class);
        $this->app->bind(ProductTypeUsagePort::class, EloquentProductTypeUsageAdapter::class);

        // Product bindings (new for issue #9)
        $this->app->bind(ProductRepositoryPort::class, EloquentProductRepository::class);
        $this->app->bind(ProductBusinessPort::class, EloquentProductBusinessRepository::class);

        // Product usage port — backed by DynamoDB (issue #13)
        $this->app->bind(ProductUsagePort::class, function () {
            return new DynamoDbProductUsageAdapter(
                noSql: $this->app->make(NoSqlPort::class),
                table: (string) \config('services.dynamodb.product_usage_table'),
            );
        });

        // Issue #10: Batch product generation ports
        $this->app->bind(PasswordGeneratorPort::class, AlphanumericPasswordGenerator::class);
        $this->app->bind(ExcelExportPort::class, PhpSpreadsheetExcelExporter::class);
        $this->app->singleton(ProductGenerationService::class, function () {
            return new ProductGenerationService(
                passwordGenerator: $this->app->make(PasswordGeneratorPort::class),
            );
        });
    }

    public function boot(): void
    {
        // Register command handlers
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('products.create_product_type', CreateProductTypeHandler::class);
        $commandBus->register('products.update_product_type', UpdateProductTypeHandler::class);
        $commandBus->register('products.report_usage', ReportUsageHandler::class);
        $commandBus->register('products.generate_products', GenerateProductsHandler::class);
        $commandBus->register('products.assign_to_user', AssignToUserHandler::class);
        $commandBus->register('products.set_target_url', SetTargetUrlHandler::class);
        $commandBus->register('products.activate_product', ActivateProductHandler::class);
        $commandBus->register('products.deactivate_product', DeactivateProductHandler::class);
        $commandBus->register('products.change_config_status', ChangeConfigStatusHandler::class);
        $commandBus->register('products.rename_product', RenameProductHandler::class);
        $commandBus->register('products.soft_remove_product', SoftRemoveProductHandler::class);
        $commandBus->register('products.restore_product', RestoreProductHandler::class);
        $commandBus->register('products.remove_product_link', RemoveProductLinkHandler::class);

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
