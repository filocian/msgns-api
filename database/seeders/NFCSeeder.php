<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NFCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('nfcs')->insert($this->emptyNfcs());
    }


    private function emptyNfcs()
    {
        $productTypeIds = DB::table('product_types')->select('id')->get();
        $now = Carbon::now();

        foreach ($productTypeIds->flatten() as $productType) {
            $rows[] = $this->buildFakeNFC($productTypeIds, $now);
        }

        return $rows;
    }

    public function buildFakeNFC(Collection $productTypeIds, Carbon $now)
    {
        return [
            'product_type_id' => $productTypeIds->random()->id,
            'created_at' => $now,
            'password' => '1234567890',
            'qty' => fake()->numberBetween([1, 10])
        ];
    }
}
