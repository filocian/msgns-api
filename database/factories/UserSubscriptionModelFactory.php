<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

/**
 * @extends Factory<UserSubscriptionModel>
 */
final class UserSubscriptionModelFactory extends Factory
{
    protected $model = UserSubscriptionModel::class;

    public function definition(): array
    {
        return [
            'user_id'                => User::factory(),
            'subscription_type_id'   => SubscriptionTypeModel::factory(),
            'billing_period'         => $this->faker->randomElement(['monthly', 'annual']),
            'stripe_subscription_id' => 'sub_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
            'status'                 => 'active',
            'current_period_start'   => now(),
            'current_period_end'     => now()->addMonth(),
            'cancelled_at'           => null,
        ];
    }
}
