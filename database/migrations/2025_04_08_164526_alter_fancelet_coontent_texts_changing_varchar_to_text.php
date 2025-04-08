<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	protected $table = 'fancelet_content_texts';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->text('en_EN')->nullable()->change();
			$table->text('es_ES')->nullable()->change();
			$table->text('ca_ES')->nullable()->change();
			$table->text('de_DE')->nullable()->change();
			$table->text('fr_FR')->nullable()->change();
			$table->text('it_IT')->nullable()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table($this->table, function (Blueprint $table) {
			$table->string('en_EN', 255)->nullable()->change();
			$table->string('es_ES', 255)->nullable()->change();
			$table->string('ca_ES', 255)->nullable()->change();
			$table->string('de_DE', 255)->nullable()->change();
			$table->string('fr_FR', 255)->nullable()->change();
			$table->string('it_IT', 255)->nullable()->change();
		});
	}
};
