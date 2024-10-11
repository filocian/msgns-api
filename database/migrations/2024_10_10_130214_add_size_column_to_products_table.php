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
			$table->string('size')->nullable()->default(null);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->dropColumn('size');
		});
	}
};
