<?php

namespace Database\Seeders;

use App\Static\StaticNFCType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypeSeeder extends Seeder
{
    protected $table = 'product_types';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table($this->table)
            ->insert($this->nfcTypes());
    }

    private function nfcTypes(): array
    {
        $now = Carbon::now();
        return array_map(function (string $nfcType) use ($now) {
            return [
                'name' => $nfcType,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, StaticNFCType::all());
    }

}
