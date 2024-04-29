<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	protected string $table = 'products_usage_stats';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_id')->constrained();
			$table->date('date_of_access');
			$table->time('hour_of_access');
			$table->integer('access_count')->default(0);
			$table->json('user_agent')->nullable();
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
