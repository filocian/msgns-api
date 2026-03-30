<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ResolveProductRedirection;

use DateTimeImmutable;
use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\Events\ProductScanned;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Shared\Core\Bus\DomainEvent;
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

        if (!$product->active) {
            throw ProductMisconfigured::notActive($product->id);
        }

        if ($product->configurationStatus->value !== ConfigurationStatus::COMPLETED) {
            throw ProductMisconfigured::incompleteConfiguration($product->id);
        }

        if (!$this->strategy->supports($product)) {
            throw NotFound::entity('redirection-strategy', $product->model->value);
        }

        $target = $this->strategy->resolve(
            $product,
            new RedirectionContext($query->browserLocales),
        );

        $now = new DateTimeImmutable();

        $product->recordEvent(new ProductScanned(
            productId: $product->id,
            userId: $product->userId,
            productName: $product->name->value,
            scannedAt: $now,
        ));

        $this->usagePort->writeUsageEvent(
            $product->id,
            $product->userId ?? 0,
            $product->name->value,
            $now,
        );

        foreach ($product->releaseEvents() as $event) {
            if ($event instanceof DomainEvent) {
                $this->eventBus->publish($event);
            }
        }

        return $target;
    }
}
