<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'configuration_status_codes';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->string('status_code')->primary();
			$table->string('label');
			$table->string('description');
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
