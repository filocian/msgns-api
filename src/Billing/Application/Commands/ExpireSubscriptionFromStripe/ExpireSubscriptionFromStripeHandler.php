<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\ExpireSubscriptionFromStripe;

use App\Models\User;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class ExpireSubscriptionFromStripeHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof ExpireSubscriptionFromStripeCommand);

        $subscription = UserSubscriptionModel::where('stripe_subscription_id', $command->stripeSubscriptionId)
            ->with('subscriptionType')
            ->firstOrFail();

        $subscription->update(['status' => 'expired']);

        /** @var User $user */
        $user = User::findOrFail($subscription->user_id);
        $user->revokePermissionTo($subscription->subscriptionType->permission_name);

        return null;
    }
}
