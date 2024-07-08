<?php

namespace Database\Importer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Nette\FileNotFoundException;

class ImporterProductModel
{
	protected ConnectionInterface $connection;
	protected array $products;
	private Collection|null $productIdentificationCache = null;
	private $cacheCount = 0;

	public function __construct(ConnectionInterface $connection)
	{
		$loadModelQuery = <<<SQL
			SELECT *
			FROM nfc
			WHERE elem_type NOT IN ('', 'twitter', 'newsroom', 'frame', 'linkedin', 'www.qrmenu.org', 'web');
			SQL;
		$this->connection = $connection;
		$this->products = $this->connection->select($loadModelQuery);
		$this->initProductIdentificationCache();
	}

	public function normalize()
	{
		try {
			$products = array_map(function ($product) {
				$productCode = $this->resolveProductCode($product->id, $product->elem_type, $product->background);

				return [
					'id' => $product->id,
					'product_type_id' => $this->resolveProductTypeId($productCode),
					'user_id' => $product->account_id,
					'model' => $product->elem_type,
					'target_url' => $product->target_url,
					'password' => $product->password,
					'usage' => $product->visits,
					'name' => $product->title,
					'description' => $product->description,
					'active' => boolval($product->active),
					'created_at' => $product->fecha_hora,
				];
			}, $this->products);

			$this->products = $products;
		} catch (\Exception $e) {
			dd($e->getMessage());
		}

		return $this;
	}

	public function export(string $fileName = 'products')
	{
		$name = $fileName . '.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->products)->toJson());
		return 'Datos exportados a ' . $filePath;
	}

	public function resolveProductCode(int $productId, string $productElemType, string $productBkg)
	{
		$productFound = $this->productIdentificationCache->first(function ($item) use ($productId) {
			return $item['product_id'] == $productId;
		});

		if ($productFound) {
			return $productFound['product_type'];
		}


		$productModelCode = $this->resolveProductModelCode($productElemType);
		$productShapeCode = $this->resolveProductShapeCode($productBkg);
		return 'S-' . $productModelCode . '-XX-' . $productShapeCode;
	}

	public function resolveProductModelCode(string $productElemType): string
	{
		return match ($productElemType) {
			'google' => 'GG',
			'instagram' => 'IG',
			'facebook' => 'FB',
			'youtube' => 'YT',
			'tiktok' => 'TK',
			'info' => 'IN',
			'whatsapp' => 'WR'
		};
	}

	public function resolveProductShapeCode(string $productBkg): string
	{
		if (str_contains($productBkg, 'round')) {
			return 'RD';
		}

		if (str_contains($productBkg, 'square')) {
			return 'SQ';
		}

		return 'RC';
	}

	public function resolveProductTypeId(string $productCode)
	{
		return match ($productCode) {
			'S-GG-XX-RC' => 1,
			'S-GG-XX-RD' => 2,
			'S-GW-XX-RC' => 3,
			'S-GW-XX-RD' => 4,
			'S-IG-XX-RC' => 5,
			'S-IG-XX-RD' => 6,
			'S-IG-XX-SQ' => 7,
			'S-FB-XX-RC' => 8,
			'S-FB-XX-RD' => 9,
			'S-YT-XX-RC' => 10,
			'S-TK-XX-RC' => 11,
			'S-IN-XX-RC' => 12,
			'S-WR-XX-RC' => 13,
			'S-WC-XX-RC' => 14,
			'S-WG-XX-RC' => 15,
			'S-WW-XX-SQ' => 16,
			'S-WG-XX-SQ' => 17,
			'P-GG-IN-RC' => 18,
			'P-GW-IN-RC' => 19,
			'P-GW-GO-RC' => 20,
			'P-GM-GO-RC' => 21,
			'T-GW-XX-RC' => 22
		};
	}

	private function initProductIdentificationCache()
	{
		$filePath = database_path('importer/ProductIdentification.csv');

		if (!file_exists($filePath)) {
			throw new FileNotFoundException();
		}

		$header = null;
		$data = array();

		if (($handle = fopen($filePath, 'r')) !== false) {
			while (($row = fgetcsv($handle, 1000, ',')) !== false) {
				if (!$header) {
					$header = $row;
				} else {
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		}

		$this->productIdentificationCache = collect($data);


		$this->cacheCount = $this->productIdentificationCache->count();
	}
}