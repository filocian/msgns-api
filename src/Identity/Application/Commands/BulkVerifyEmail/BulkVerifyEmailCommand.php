<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkVerifyEmail;

use Src\Shared\Core\Bus\Command;

/**
 * Command to verify email for multiple users in bulk.
 */
final readonly class BulkVerifyEmailCommand implements Command
{
    /**
     * @param array<int> $userIds
     */
    public function __construct(
        public array $userIds,
    ) {}

    public function commandName(): string
    {
        return 'identity.bulk_verify_email';
    }
}
