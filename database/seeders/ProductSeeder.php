<?php

namespace Database\Seeders;

use App\Static\Product\StaticProductTypes;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    protected $table = 'products';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table($this->table)
            ->insert($this->products());
    }

    private function products(): array
    {
        $now = Carbon::now();
        $productsQty = 10;
        $google = StaticProductTypes::GR_STICKER;
        unset($google['code']);
        $google['config'] = json_encode($google['config']);
        $instagram = StaticProductTypes::IG_STICKER;
        unset($instagram['code']);
        $instagram['config'] = json_encode($instagram['config']);
        $facebook = StaticProductTypes::FB_STICKER;
        unset($facebook['code']);
        $facebook['config'] = json_encode($facebook['config']);
        $productList = [];

        for ($x=0; $x<$productsQty; $x++){
            $productList[] = array_merge($google, [
                'product_type_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $productList[] = array_merge($instagram, [
                'product_type_id' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $productList[] = array_merge($facebook, [
                'product_type_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $productList;
    }
}
