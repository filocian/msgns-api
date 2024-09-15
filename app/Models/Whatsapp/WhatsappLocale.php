<?php

declare(strict_types=1);

namespace App\Models\Whatsapp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WhatsappLocale extends Model
{
	use HasFactory;

	protected $table = 'whatsapp_locales';
	protected $fillable = ['code', ];

	public function whatsappMessages()
	{
		return $this->hasMany(WhatsappMessage::class, 'locale_id');
	}
}
