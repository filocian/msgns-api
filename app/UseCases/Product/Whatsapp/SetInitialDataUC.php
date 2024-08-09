<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class SetInitialDataUC implements UseCaseContract
{
	public function __construct(
		private AddPhoneUC          $addPhoneUC,
		private AddMessageUC        $addMessageUC,
		private SetDefaultMessageUC $setDefaultMessageUC,
		private ProductService      $productService
	)
	{
	}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, phone_prefix: string, phone_number: string, message_locale_id: int, message: string}|null $data
	 * @param array|null $opts
	 * @return WhatsappMessageDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappMessageDto
	{
		$productId = $data['id'];
		$phone = $data['phone_number'];
		$prefix = $data['phone_prefix'];
		$localeId = $data['message_locale_id'];
		$message = $data['message'];

		try {
			$product = Product::findById($productId);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}

		$configStatus = $this->productService
			->resolveConfigurationStatus($product, ProductConfigurationStatus::$STATUS_TARGET_SET);

		$config = [
			'configuration_status' => $configStatus,
		];

		$product->update($config);

		$savedPhone = $this->addPhoneUC->run([
			'id' => $productId,
			'phone_prefix' => $prefix,
			'phone_number' => $phone,
		]);

		$messageDto = $this->addMessageUC->run([
			'id' => $productId,
			'phone_id' => $savedPhone->id,
			'message_locale_id' => $localeId,
			'message' => $message
		]);

		$this->setDefaultMessageUC->run([
			'product_id' => $productId,
			'message_id' => $messageDto->id
		]);

		return $messageDto;
	}
}
