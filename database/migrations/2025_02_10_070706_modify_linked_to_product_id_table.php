<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	protected string $table = 'products';

	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->dropForeign(['linked_to_product_id']);
			$table->dropUnique(['linked_to_product_id']);
			$table->foreign('linked_to_product_id')
				->references('id')
				->on($this->table)
				->onDelete('cascade')
				->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->dropForeign(['linked_to_product_id']);
			$table->unique('linked_to_product_id');
			$table->foreign('linked_to_product_id')
				->references('id')
				->on($this->table)
				->onDelete('cascade')
				->onUpdate('cascade');
		});
	}
};
