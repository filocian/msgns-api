<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFC extends Model
{
    use HasFactory;

    protected $table = 'nfcs';

    public $hidden = [
        'password'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type(): HasOne
    {
        return $this->hasOne(ProductType::class, 'id', 'product_type_id');
    }
}
