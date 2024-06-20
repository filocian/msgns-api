<?php
namespace Database\Importer;
use Illuminate\Database\ConnectionInterface;

class ImporterProductModel
{
	protected ConnectionInterface $connection;
	protected string $model;
	protected array $products;
	private array $productsCache;

	public function __construct(ConnectionInterface $connection, string $model)
	{
		$loadModelQuery = <<<SQL
			SELECT *
			FROM `nfc`
			WHERE `elem_type` LIKE '%$model%';
			SQL;
		$this->model = $model;
		$this->connection = $connection;
		$this->products = $this->connection->select($loadModelQuery);
	}

	public function notOwned()
	{
		$this->productsCache = array_filter($this->products, function ($product) {
			return $product->account_id == 0;
		});

		return $this;
	}

	public function hasType(string $type)
	{
		$this->productsCache = array_filter($this->products, function ($product) use($type){
			return $product->product_type == $type;
		});

		return $this;
	}

	public function hasShape(array $shapes)
	{
		$this->productsCache = array_filter($this->products, function ($product) use ($shapes) {
			for($x=0; $x<count($shapes); $x++){
				if(str_contains($product->sellers_tags, $shapes[$x])){
					return true;
				}
			}

			return false;
		});

		return $this;
	}

	public function get()
	{
		return $this->productsCache;
	}

	public function export(string $fileName = null){
		$name = $fileName ?? $this->model . '.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->productsCache)->toJson());
		return 'Datos exportados a ' . $filePath;
	}
}