<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappMessages;

use Src\Products\Application\Resources\WhatsappMessageResource;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListWhatsappMessagesHandler implements QueryHandler
{
    public function __construct(
        private readonly WhatsappMessageRepositoryPort $messageRepository,
    ) {}

    /**
     * @return list<WhatsappMessageResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListWhatsappMessagesQuery);

        $messages = $this->messageRepository->findByProductId($query->productId);

        return array_map(
            static fn ($message): WhatsappMessageResource => WhatsappMessageResource::fromEntity($message),
            $messages,
        );
    }
}
