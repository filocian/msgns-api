<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_type_id' => ProductType::factory(),
            'user_id' => User::factory(),
            'model' => 'nfc',
            'linked_to_product_id' => null,
            'target_url' => fake()->optional()->url(),
            'password' => fake()->unique()->bothify('pass-########'),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'active' => true,
            'configuration_status' => ConfigurationStatus::NOT_STARTED,
            'assigned_at' => now(),
            'size' => null,
            'usage' => fake()->numberBetween(0, 500),
        ];
    }
}
