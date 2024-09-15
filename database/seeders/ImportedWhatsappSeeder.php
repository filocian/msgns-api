<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Whatsapp\WhatsappLocale;
use App\Models\Whatsapp\WhatsappMessage;
use App\Models\Whatsapp\WhatsappPhone;
use Illuminate\Database\Seeder;
use Nette\FileNotFoundException;

final class ImportedWhatsappSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$filePath = database_path('importer/data/whatsapp_channels.json');

		if (!file_exists($filePath)) {
			throw new FileNotFoundException();
		}

		$seedFile = file_get_contents($filePath);
		$whatsappProductData = json_decode($seedFile);

		foreach ($whatsappProductData as $productData) {
			$productPhones = $productData->phones;
			$productMessages = $productData->messages;

			foreach ($productPhones as $phone) {
				$currentPhone = WhatsappPhone::updateOrCreate([
					'product_id' => $productData->product_id,
					'prefix' => $phone->prefix,
					'phone' => $phone->phone,
				]);
			}

			foreach ($productMessages as $message) {
				$phone = WhatsappPhone::where([
					'phone' => $message->phone,
					'prefix' => $message->prefix,
				])->first();
				$locale = WhatsappLocale::where([
					'code' => $message->locale,
				])->first();

				WhatsappMessage::create([
					'product_id' => $productData->product_id,
					'phone_id' => $phone->id,
					'locale_id' => $locale->id,
					'message' => $message->text,
					'default' => $message->default,
				]);
			}
		}
	}
}
