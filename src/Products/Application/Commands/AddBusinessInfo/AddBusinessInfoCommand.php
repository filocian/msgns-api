<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddBusinessInfo;

use Src\Shared\Core\Bus\Command;

/**
 * @param array<string, mixed> $types
 * @param array<string, mixed>|null $placeTypes
 */
final readonly class AddBusinessInfoCommand implements Command
{
    /**
     * @param array<string, mixed> $types
     * @param array<string, mixed>|null $placeTypes
     */
    public function __construct(
        public int $productId,
        public int $userId,
        public bool $notABusiness,
        public array $types,
        public ?string $name,
        public ?array $placeTypes,
        public ?string $size,
    ) {}

    public function commandName(): string
    {
        return 'products.add_business_info';
    }
}
