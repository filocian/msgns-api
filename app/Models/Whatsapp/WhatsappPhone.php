<?php

declare(strict_types=1);

namespace App\Models\Whatsapp;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WhatsappPhone extends Model
{
	use HasFactory;
	protected $table = 'whatsapp_phones';
	protected $fillable = ['product_id', 'phone', 'prefix', ];

	public function whatsappMessages()
	{
		return $this->hasMany(WhatsappMessage::class, 'phone_id');
	}

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id');
	}
}
