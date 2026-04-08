<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappLocales;

use Src\Products\Application\Resources\WhatsappLocaleResource;
use Src\Products\Domain\Ports\WhatsappLocaleRepositoryPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListWhatsappLocalesHandler implements QueryHandler
{
    public function __construct(
        private readonly WhatsappLocaleRepositoryPort $localeRepository,
    ) {}

    /**
     * @return list<WhatsappLocaleResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListWhatsappLocalesQuery);

        $locales = $this->localeRepository->findAll();

        return array_map(
            static fn ($locale): WhatsappLocaleResource => WhatsappLocaleResource::fromEntity($locale),
            $locales,
        );
    }
}
