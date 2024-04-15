<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTypes extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'config_template'];

    protected $casts = [
        'config_template' => 'array',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'product_type_id');
    }
}
