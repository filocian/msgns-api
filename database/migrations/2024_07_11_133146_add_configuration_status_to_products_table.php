<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'products';
    /**
     * Run the migrations.
     */
	public function up(): void
	{
		Schema::table('products', function (Blueprint $table) {
			$table->string('configuration_status')->default('not-started');
			$table->foreign('configuration_status')->references('status_code')->on('configuration_status_codes');
			$table->index('configuration_status'); // Adding the index
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('products', function (Blueprint $table) {
			$table->dropForeign(['configuration_status']);
			$table->dropIndex(['configuration_status']); // Dropping the index
			$table->dropColumn('configuration_status');
		});
	}
};
