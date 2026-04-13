<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\CancelClassicSubscription;

use Src\Ai\Domain\Errors\SubscriptionNotFound;
use Src\Ai\Domain\Ports\ClassicSubscriptionBrokerPort;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Ports\TransactionPort;

final class CancelClassicSubscriptionHandler implements CommandHandler
{
    public function __construct(
        private readonly ClassicSubscriptionBrokerPort $broker,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): UserSubscriptionModel
    {
        assert($command instanceof CancelClassicSubscriptionCommand);

        /** @var UserSubscriptionModel|null $userSubscription */
        $userSubscription = UserSubscriptionModel::query()
            ->where('user_id', $command->userId)
            ->where('status', 'active')
            ->first();

        if ($userSubscription === null) {
            throw SubscriptionNotFound::forUser($command->userId);
        }

        // Cancel at period end in Stripe — does NOT revoke permission (that's BE-7's job)
        $this->broker->cancelSubscription($command->userId);

        return $this->transaction->run(function () use ($userSubscription): UserSubscriptionModel {
            $userSubscription->status = 'cancelled';
            $userSubscription->cancelled_at = now();
            $userSubscription->save();

            return $userSubscription;
        });
    }
}
