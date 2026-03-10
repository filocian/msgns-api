<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	/**
	 * SQLite cannot apply the MySQL-specific collation used in production.
	 */
	private function usesSqlite(): bool
	{
		return Schema::getConnection()->getDriverName() === 'sqlite';
	}

	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$column = $table->longText('user_agent')
				->nullable()
				->default(null);

			if (! $this->usesSqlite()) {
				$column->collation('utf8mb4_unicode_ci');
			}

			$column->change();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->json('user_agent')->default('{}')->change();
		});
	}
};
