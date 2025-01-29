<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'fancelet_content_texts';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('gallery_id')->constrained('fancelet_content_gallery');
			$table->string('layout_reference')->nullable()->default(null);
			$table->integer('order')->nullable()->default(null);
			$table->string('en_EN')->nullable()->default(null);
			$table->string('es_ES')->nullable()->default(null);
			$table->string('ca_ES')->nullable()->default(null);
			$table->string('de_DE')->nullable()->default(null);
			$table->string('fr_FR')->nullable()->default(null);
			$table->string('it_IT')->nullable()->default(null);
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
