<?php

declare(strict_types=1);

namespace App\Models\Whatsapp;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WhatsappMessage extends Model
{
	use HasFactory;
	protected $table = 'whatsapp_messages';
	protected $fillable = ['product_id', 'phone_id', 'locale_id', 'message', 'default', ];
	protected $casts = [
		'default' => 'bool',
	];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}

	public function locale()
	{
		return $this->belongsTo(WhatsappLocale::class);
	}

	public function phone()
	{
		return $this->belongsTo(WhatsappPhone::class);
	}
}
