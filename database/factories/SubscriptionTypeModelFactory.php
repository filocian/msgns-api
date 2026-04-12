<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

/**
 * @extends Factory<SubscriptionTypeModel>
 */
final class SubscriptionTypeModelFactory extends Factory
{
    protected $model = SubscriptionTypeModel::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name'                    => ucwords((string) $name),
            'slug'                    => Str::slug((string) $name),
            'description'             => $this->faker->optional()->sentence(),
            'mode'                    => $this->faker->randomElement(['classic', 'prepaid']),
            'billing_periods'         => fn (array $attrs) => $attrs['mode'] === 'classic'
                ? $this->faker->randomElements(['monthly', 'annual'], rand(1, 2))
                : null,
            'base_price_cents'        => $this->faker->numberBetween(100, 2000),
            'permission_name'         => 'ai.' . Str::slug((string) $name),
            'google_review_limit'     => $this->faker->numberBetween(0, 200),
            'instagram_content_limit' => $this->faker->numberBetween(0, 100),
            'stripe_product_id'       => null,
            'stripe_price_ids'        => null,
            'is_active'               => true,
        ];
    }
}
