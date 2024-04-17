<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $table = 'product_types';
    protected $casts = [
        'config_template' => 'array',
    ];

    public static function findById(int $productId): ProductType
    {
        return self::findOrFail($productId);
    }

//    public function nfcs()
//    {
//        return $this->hasMany(NFC::class);
//    }
}
