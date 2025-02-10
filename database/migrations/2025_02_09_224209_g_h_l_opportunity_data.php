<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	protected string $table = 'ghl_opportunity_data';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
			$table->string('opportunity_id');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists($this->table);
	}
};
