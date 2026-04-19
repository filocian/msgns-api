<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\SubscribeToClassicPlan;

use App\Models\User;
use DateTime;
use Src\Ai\Domain\Errors\SubscriptionAlreadyActive;
use Src\Ai\Domain\Ports\ClassicSubscriptionBrokerPort;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

final class SubscribeToClassicPlanHandler implements CommandHandler
{
    public function __construct(
        private readonly ClassicSubscriptionBrokerPort $broker,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): UserSubscriptionModel
    {
        assert($command instanceof SubscribeToClassicPlanCommand);

        $hasActiveSub = UserSubscriptionModel::query()
            ->where('user_id', $command->userId)
            ->whereIn('status', ['active', 'cancelled'])
            ->exists();

        if ($hasActiveSub) {
            throw SubscriptionAlreadyActive::forUser($command->userId);
        }

        /** @var SubscriptionTypeModel|null $subscriptionType */
        $subscriptionType = SubscriptionTypeModel::query()->find($command->subscriptionTypeId);

        if ($subscriptionType === null) {
            throw NotFound::entity('subscription_type', (string) $command->subscriptionTypeId);
        }

        $stripePriceIds = $subscriptionType->stripe_price_ids ?? [];
        $priceId = $stripePriceIds[$command->billingPeriod] ?? null;

        if ($priceId === null) {
            throw NotFound::entity('stripe_price', $command->billingPeriod);
        }

        // Stripe call outside DB transaction — SubscriptionRequires3DS propagates up on 3DS
        $stripeData = $this->broker->createSubscription(
            $command->userId,
            $priceId,
            $command->paymentMethodId,
        );

        return $this->transaction->run(function () use ($command, $subscriptionType, $stripeData): UserSubscriptionModel {
            /** @var UserSubscriptionModel $userSubscription */
            $userSubscription = UserSubscriptionModel::query()->create([
                'user_id'                => $command->userId,
                'subscription_type_id'   => $command->subscriptionTypeId,
                'billing_period'         => $command->billingPeriod,
                'stripe_subscription_id' => $stripeData['stripe_subscription_id'],
                'status'                 => 'active',
                'current_period_start'   => new DateTime('@' . $stripeData['current_period_start']),
                'current_period_end'     => new DateTime('@' . $stripeData['current_period_end']),
            ]);

            /** @var User $user */
            $user = User::findOrFail($command->userId);
            $user->givePermissionTo($subscriptionType->permission_name);

            return $userSubscription;
        });
    }
}
