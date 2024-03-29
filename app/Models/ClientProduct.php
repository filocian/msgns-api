<?php

namespace App\Models;

use App\Static\StaticProductType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientProduct extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $attributes = [
        'type' => StaticProductType::NFC
    ];
}
