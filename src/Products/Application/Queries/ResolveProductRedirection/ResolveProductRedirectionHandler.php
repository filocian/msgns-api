<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ResolveProductRedirection;

use DateTimeImmutable;
use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductScanned;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Infrastructure\Cache\ProductRedirectionCacheService;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Errors\NotFound;

final class ResolveProductRedirectionHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $repository,
        private readonly ProductRedirectionStrategy $strategy,
        private readonly ProductUsagePort $usagePort,
        private readonly EventBus $eventBus,
        private readonly ProductRedirectionCacheService $cacheService,
        private readonly string $frontUrl,
    ) {}

    public function handle(Query $query): RedirectionTarget
    {
        assert($query instanceof ResolveProductRedirectionQuery);

        $product = $this->repository->findByIdAndPassword(
            $query->productId,
            $query->password,
        );

        if ($product === null) {
            throw NotFound::entity('product', (string) $query->productId);
        }

        // Step 2: Virgin
        if ($product->isVirgin()) {
            return RedirectionTarget::frontendRoute($this->stepperUrl($product->id, $query->password));
        }

        // Step 3: Disabled
        if ($product->isDisabled()) {
            return RedirectionTarget::frontendRoute($this->disabledUrl($product->id));
        }

        // Step 4: Misconfigured, cannot bypass
        if ($product->isMisconfigured() && !$product->canBypassMisconfiguration()) {
            return RedirectionTarget::frontendRoute($this->stepperUrl($product->id, $query->password));
        }

        // Steps 5+6: Strategy-eligible — consult cache first
        $cached = $this->resolveFromCache($query->productId, $query->password);
        if ($cached !== null) {
            $this->trackUsage(
                userId: $cached['meta']['userId'],
                productId: $query->productId,
                productName: $cached['meta']['productName'],
            );

            return $cached['target'];
        }

        return $this->resolveViaStrategy($product, $query);
    }

    private function stepperUrl(int $id, string $password): string
    {
        return "{$this->frontUrl}/products/{$id}/configure?password={$password}";
    }

    private function disabledUrl(int $id): string
    {
        return "{$this->frontUrl}/product/disabled?id={$id}";
    }

    private function resolveViaStrategy(Product $product, ResolveProductRedirectionQuery $query): RedirectionTarget
    {
        if (!$this->strategy->supports($product)) {
            throw NotFound::entity('redirection-strategy', $product->model->value);
        }

        $target = $this->strategy->resolve(
            $product,
            new RedirectionContext($query->browserLocales),
        );

        try {
            $this->cacheService->put($query->productId, $target, [
                'userId' => $product->userId,
                'productName' => $product->name->value,
                'password' => $query->password,
            ]);
        } catch (\Throwable) {
            // Cache is an optimization only.
        }

        $this->trackUsage(
            userId: $product->userId,
            productId: $product->id,
            productName: $product->name->value,
        );

        return $target;
    }

    /**
     * @return array{target: RedirectionTarget, meta: array{userId: ?int, productName: string, password: string}}|null
     */
    private function resolveFromCache(int $productId, string $password): ?array
    {
        try {
            $cached = $this->cacheService->get($productId);
        } catch (\Throwable) {
            return null;
        }

        if ($cached === null || $cached['meta']['password'] !== $password) {
            return null;
        }

        return $cached;
    }

    private function trackUsage(?int $userId, int $productId, string $productName): void
    {
        $now = new DateTimeImmutable();

        try {
            $this->usagePort->writeUsageEvent(
                $productId,
                $userId ?? 0,
                $productName,
                $now,
            );
        } catch (\Throwable) {
            // Usage persistence must not break redirection.
        }

        $this->eventBus->publish(new ProductScanned(
            productId: $productId,
            userId: $userId,
            productName: $productName,
            scannedAt: $now,
        ));
    }
}
