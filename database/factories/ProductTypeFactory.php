<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductType>
 */
final class ProductTypeFactory extends Factory
{
    protected $model = ProductType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('type-####'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'image_ref' => fake()->uuid(),
            'primary_model' => 'nfc',
            'secondary_model' => null,
        ];
    }
}
