<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AssignToUser;

use Src\Shared\Core\Bus\Command;

final readonly class AssignToUserCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'products.assign_to_user';
    }
}
