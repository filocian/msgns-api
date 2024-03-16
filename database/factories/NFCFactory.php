<?php

namespace Database\Factories;

use App\Models\ClientProduct;
use Carbon\Carbon;
use Database\Seeders\NFCSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientProduct>
 */
class NFCFactory extends Factory
{
    protected $model = ClientProduct::class;
    private NFCSeeder $seeder;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = Carbon::now();
        $productTypeIds = DB::table('product_types')->select('id')->get();

        return $this->getSeeder()->buildFakeNFC($productTypeIds, $now);
    }

    private function getSeeder(): NFCSeeder
    {
        if (!$this->seeder) {
            $this->seeder = new NFCSeeder();
        }

        return $this->seeder;
    }
}
