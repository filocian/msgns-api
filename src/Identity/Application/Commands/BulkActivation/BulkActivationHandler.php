<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkActivation;

use Src\Identity\Application\Resources\BulkActionResultResource;
use Src\Identity\Application\Resources\BulkSummary;
use Src\Identity\Application\Resources\BulkUserResult;
use Src\Identity\Domain\Events\UserActivated;
use Src\Identity\Domain\Events\UserDeactivated;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;

/**
 * Handler for bulk activation/deactivation.
 * Continue-on-failure semantics: processes all users and reports per-user status.
 * Publishes UserActivated/UserDeactivated events for state changes.
 */
final class BulkActivationHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): BulkActionResultResource
    {
        assert($command instanceof BulkActivationCommand);

        $results = [];
        $succeeded = 0;
        $failed = 0;

        foreach ($command->userIds as $userId) {
            $user = $this->repo->findById($userId);

            if ($user === null) {
                $results[] = new BulkUserResult(
                    userId: $userId,
                    status: 'failed',
                    code: 'user_not_found',
                    message: 'User not found',
                );
                $failed++;
                continue;
            }

            try {
                if ($command->active) {
                    // Try to activate
                    try {
                        $user->activate();
                        $this->repo->save($user);
                        $this->eventBus->publish(new UserActivated(
                            userId: $user->id,
                            activatedBy: $command->performedBy,
                        ));
                        $results[] = new BulkUserResult(
                            userId: $userId,
                            status: 'updated',
                            message: 'User activated',
                        );
                    } catch (ValidationFailed $e) {
                        if ($e->getMessage() === 'user_already_active') {
                            $results[] = new BulkUserResult(
                                userId: $userId,
                                status: 'unchanged',
                                message: 'User already active',
                            );
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    // Try to deactivate
                    try {
                        $user->deactivate();
                        $this->repo->save($user);
                        $this->eventBus->publish(new UserDeactivated(
                            userId: $user->id,
                            deactivatedBy: $command->performedBy,
                        ));
                        $results[] = new BulkUserResult(
                            userId: $userId,
                            status: 'updated',
                            message: 'User deactivated',
                        );
                    } catch (ValidationFailed $e) {
                        if ($e->getMessage() === 'user_already_inactive') {
                            $results[] = new BulkUserResult(
                                userId: $userId,
                                status: 'unchanged',
                                message: 'User already inactive',
                            );
                        } else {
                            throw $e;
                        }
                    }
                }
                $succeeded++;
            } catch (\Exception $e) {
                $results[] = new BulkUserResult(
                    userId: $userId,
                    status: 'failed',
                    code: 'activation_failed',
                    message: $e->getMessage(),
                );
                $failed++;
            }
        }

        return new BulkActionResultResource(
            summary: new BulkSummary(
                requested: count($command->userIds),
                succeeded: $succeeded,
                failed: $failed,
            ),
            results: $results,
        );
    }
}
