<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\DTO\Whatsapp\WhatsappLocaleDto;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Models\Product;
use App\Models\Whatsapp\WhatsappLocale;
use App\Models\Whatsapp\WhatsappMessage;
use App\Models\Whatsapp\WhatsappPhone;

final readonly class WhatsappResolverUC implements UseCaseContract
{
	private readonly string $whatsappUrl;

	/*https://api.whatsapp.com/send/?phone=PrefijoYNumeroSeguido&text=TEXTOPROGRAMADO&type=phone_number&app_absent=0*/

	public function __construct()
	{
		$this->whatsappUrl = 'https://api.whatsapp.com/send/?phone=<<phone>>&text=<<message>>&type=phone_number&app_absent=0';
	}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{productModel: Product, browserLocales: string}|null $data
	 * @param array|null $opts
	 * @return string|null
	 */
	public function run(mixed $data = null, ?array $opts = null): string|null
	{
		$productModel = $data['productModel'];
		$productDto = ProductDto::fromModel($productModel);
		$browserLocales = $data['browserLocales'];
		$locale = $this->parseRequestLocale($browserLocales);
		$message = $this->resolveProductMessage($productDto->id, $locale);

		if(!$message){
			return null;
		}

		$fullPhoneNumber = $message->phone->prefix . $message->phone->phone;

		return str_replace(
			['<<phone>>', '<<message>>'],
			[$fullPhoneNumber, rawurlencode($message->message)],
			$this->whatsappUrl
		);
	}

	private function resolveProductMessage(int $productId, string $browserLocale): ?WhatsappMessageDto
	{
		$locale = $this->resolveMessageLocale($browserLocale);
		$query = WhatsappMessage::query()
			->where('product_id', '=', $productId);

		if($locale){
			$query->where('locale_id', '=', $locale->id);
		} else{
			$query->where('default', '=', '1');
		}

		$message = $query->first();

		if (!$message) {
			$message = WhatsappMessage::query()
				->where('product_id', '=', $productId)
				->first();
		}

		if(!$message){
			return null;
		}

		return WhatsappMessageDto::fromModel($message);
	}

	private function resolveMessageLocale(string $browserLocale): ?WhatsappLocaleDto
	{
		$locale = WhatsappLocale::query()
		->where('code', 'like', '%' . $browserLocale . '%')
		->first();

		if(isset($locale)){
			return WhatsappLocaleDto::fromModel($locale);
		}

		return null;
	}

	private function parseRequestLocale(string $headerLocales): string
	{
		$parts = explode(';', $headerLocales);
		$locales = explode(',', $parts[0]);
		$browserLanguages = [];

		foreach ($locales as $locale) {
			if (str_contains($locale, '-')) {
				$browserLanguages[] = explode('-', $locale)[0];
			} else {
				$browserLanguages[] = $locale;
			}
		}

		return $browserLanguages[0];
	}
}
