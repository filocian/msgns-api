<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBusiness>
 */
final class ProductBusinessFactory extends Factory
{
    protected $model = ProductBusiness::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'not_a_business' => false,
            'name' => fake()->company(),
            'types' => ['restaurant' => true],
            'place_types' => null,
            'size' => 'small',
        ];
    }
}
