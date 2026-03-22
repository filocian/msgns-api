<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkPasswordReset;

use Src\Shared\Core\Bus\Command;

/**
 * Command to trigger password reset for multiple users in bulk.
 * Reuses the standard password reset flow.
 */
final readonly class BulkPasswordResetCommand implements Command
{
    /**
     * @param array<int> $userIds
     */
    public function __construct(
        public array $userIds,
    ) {}

    public function commandName(): string
    {
        return 'identity.bulk_password_reset';
    }
}
