<?php

namespace Database\Importer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Nette\FileNotFoundException;

class ImporterNewProductModel
{
	protected ConnectionInterface $connection;
	protected array $products;
	private Collection|null $productIdentificationCache = null;
	private $cacheCount = 0;

	public function __construct(ConnectionInterface $connection)
	{
		$loadModelQuery = <<<SQL
			SELECT *
			FROM new_products;
			SQL;
		$this->connection = $connection;
		$this->products = $this->connection->select($loadModelQuery);
	}

	public function normalize()
	{
		try {
			$products = array_map(function ($product) {
				return [
					'id' => $product->id,
					'product_type_id' => $this->resolveProductTypeId($product->name),
					'user_id' => null,
					'model' => explode(' ', $product->description)[1],
					'target_url' => null,
					'password' => $product->password,
					'usage' => 0,
					'name' => explode(' ', $product->description)[1] . ' ('. $product->id .')',
					'description' => $product->description,
					'configuration_status' => 'not-started',
					'active' => true,
					'created_at' => $product->created_at,
				];
			}, $this->products);

			$this->products = $products;
		} catch (\Exception $e) {
			dd($e->getMessage());
		}

		return $this;
	}

	public function export(string $fileName = 'new_products')
	{
		$name = $fileName . '.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->products)->toJson());
		return 'Datos exportados a ' . $filePath;
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
			'P-GW-IG-RC' => 19,
			'P-GW-GO-RC' => 20,
			'P-GM-GO-RC' => 21,
			'T-GW-XX-RC' => 22
		};
	}
}