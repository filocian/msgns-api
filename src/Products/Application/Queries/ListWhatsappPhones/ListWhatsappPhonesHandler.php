<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappPhones;

use Src\Products\Application\Resources\WhatsappPhoneResource;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListWhatsappPhonesHandler implements QueryHandler
{
    public function __construct(
        private readonly WhatsappPhoneRepositoryPort $phoneRepository,
    ) {}

    /**
     * @return list<WhatsappPhoneResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListWhatsappPhonesQuery);

        $phones = $this->phoneRepository->findByProductId($query->productId);

        return array_map(
            static fn ($phone): WhatsappPhoneResource => WhatsappPhoneResource::fromEntity($phone),
            $phones,
        );
    }
}
