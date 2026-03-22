<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkActivation;

use Src\Shared\Core\Bus\Command;

/**
 * Command to activate/deactivate multiple users in bulk.
 */
final readonly class BulkActivationCommand implements Command
{
    /**
     * @param array<int> $userIds
     */
    public function __construct(
        public array $userIds,
        public bool $active,
        public int $performedBy,
    ) {}

    public function commandName(): string
    {
        return 'identity.bulk_activation';
    }
}
