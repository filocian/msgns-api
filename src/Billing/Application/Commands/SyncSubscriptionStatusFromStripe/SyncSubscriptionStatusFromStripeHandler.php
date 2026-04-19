<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\SyncSubscriptionStatusFromStripe;

use App\Models\User;
use Carbon\Carbon;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class SyncSubscriptionStatusFromStripeHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof SyncSubscriptionStatusFromStripeCommand);

        $subscription = UserSubscriptionModel::where('stripe_subscription_id', $command->stripeSubscriptionId)
            ->with('subscriptionType')
            ->firstOrFail();

        $updates = ['status' => $command->newStatus];

        if ($command->currentPeriodEnd !== null) {
            $updates['current_period_end'] = Carbon::createFromTimestamp($command->currentPeriodEnd);
        }

        $subscription->update($updates);

        if ($command->newStatus === 'active') {
            /** @var User $user */
            $user = User::findOrFail($subscription->user_id);
            $user->givePermissionTo($subscription->subscriptionType->permission_name);
        }

        return null;
    }
}
