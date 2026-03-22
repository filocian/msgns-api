<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkChangeEmail;

use Src\Shared\Core\Bus\Command;

/**
 * Command to change email for multiple users in bulk.
 * Uses atomic transaction - all changes succeed or all fail.
 */
final readonly class BulkChangeEmailCommand implements Command
{
    /**
     * @param array<int, string> $changes Map of user_id => normalized_email
     */
    public function __construct(
        public array $changes,
    ) {}

    public function commandName(): string
    {
        return 'identity.bulk_change_email';
    }
}
