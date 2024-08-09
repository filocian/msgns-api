<?php

namespace App\Models\Whatsapp;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLocale extends Model
{
    use HasFactory;

	protected $table = 'whatsapp_locales';
	protected $fillable = [
		'code',
	];

	public function whatsappMessages()
	{
		return $this->hasMany(WhatsappMessage::class, 'locale_id');
	}
}
