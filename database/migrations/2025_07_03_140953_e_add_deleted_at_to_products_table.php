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
			$table->softDeletes();
			$table->index('deleted_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->dropSoftDeletes();
			$table->dropIndex(['deleted_at']);
		});
	}
};
