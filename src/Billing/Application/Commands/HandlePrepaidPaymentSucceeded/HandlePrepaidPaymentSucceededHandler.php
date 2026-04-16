<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\HandlePrepaidPaymentSucceeded;

use App\Models\User;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Ports\TransactionPort;

final class HandlePrepaidPaymentSucceededHandler implements CommandHandler
{
    public function __construct(
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof HandlePrepaidPaymentSucceededCommand);

        // Non-prepaid PaymentIntent: no metadata → graceful ignore
        if (empty($command->metadata['prepaid_package_id']) || empty($command->metadata['user_id'])) {
            return null;
        }

        // Sync path: balance already exists → graceful ignore
        $existingBalance = UserPrepaidBalanceModel::where('stripe_payment_intent_id', $command->paymentIntentId)->first();
        if ($existingBalance !== null) {
            return null;
        }

        $packageId = (int) $command->metadata['prepaid_package_id'];
        $userId    = (int) $command->metadata['user_id'];

        /** @var PrepaidPackageModel $package */
        $package = PrepaidPackageModel::findOrFail($packageId);

        $this->transaction->run(function () use ($userId, $packageId, $package, $command): void {
            UserPrepaidBalanceModel::create([
                'user_id'                          => $userId,
                'prepaid_package_id'               => $packageId,
                'google_review_requests_remaining' => $package->google_review_limit,
                'instagram_requests_remaining'     => $package->instagram_content_limit,
                'purchased_at'                     => now(),
                'stripe_payment_intent_id'         => $command->paymentIntentId,
            ]);

            /** @var User $user */
            $user = User::findOrFail($userId);
            $user->givePermissionTo($package->permission_name);
        });

        return null;
    }
}
