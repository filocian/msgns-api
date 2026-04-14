<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\PurchasePrepaidPackage;

use App\Models\User;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Src\Ai\Domain\Errors\PackageInactiveException;
use Src\Ai\Domain\Errors\PackageNotFoundException;
use Src\Ai\Domain\Ports\PrepaidChargePort;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Ports\TransactionPort;
use Stripe\Exception\ApiErrorException;

final class PurchasePrepaidPackageHandler implements CommandHandler
{
    public function __construct(
        private readonly PrepaidChargePort $chargePort,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof PurchasePrepaidPackageCommand);

        /** @var PrepaidPackageModel|null $package */
        $package = PrepaidPackageModel::query()->find($command->packageId);

        if ($package === null) {
            throw PackageNotFoundException::withId($command->packageId);
        }

        if (! $package->active) {
            throw PackageInactiveException::withId($command->packageId);
        }

        try {
            $payment = $this->chargePort->charge(
                $command->userId,
                $package->price_cents,
                $command->paymentMethodId,
            );
        } catch (IncompletePayment $e) {
            // 3DS / payment action required
            return [
                'status'        => 'requires_action',
                'client_secret' => $e->payment->clientSecret(),
            ];
        } catch (ApiErrorException) {
            return [
                'status'  => 'failed',
                'message' => 'Payment was declined. Please try a different payment method.',
            ];
        }

        $balance = $this->transaction->run(function () use ($command, $package, $payment): UserPrepaidBalanceModel {
            /** @var UserPrepaidBalanceModel $balance */
            $balance = UserPrepaidBalanceModel::query()->create([
                'user_id'                  => $command->userId,
                'prepaid_package_id'       => $package->id,
                'requests_remaining'       => $package->requests_included,
                'purchased_at'             => now(),
                'stripe_payment_intent_id' => $payment->id,
            ]);

            /** @var User $user */
            $user = User::findOrFail($command->userId);
            $user->givePermissionTo($package->permission_name);

            return $balance;
        });

        return [
            'status'  => 'succeeded',
            'balance' => $balance,
        ];
    }
}
