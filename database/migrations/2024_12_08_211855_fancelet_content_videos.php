<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'fancelet_content_videos';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('gallery_id')->constrained('fancelet_content_gallery');
			$table->integer('order')->nullable()->default(null);
			$table->string('en_EN_url')->nullable()->default(null);
			$table->string('es_ES_url')->nullable()->default(null);
			$table->string('ca_ES_url')->nullable()->default(null);
			$table->string('de_DE_url')->nullable()->default(null);
			$table->string('fr_FR_url')->nullable()->default(null);
			$table->string('it_IT_url')->nullable()->default(null);
			$table->integer('likes')->default(0);

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
