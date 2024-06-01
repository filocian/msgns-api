<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'product_business';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_id')->constrained('products');
			$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
			$table->string('name', length: 150)->nullable()->default(null);
			$table->json('types');
			$table->json('place_types')->nullable()->default(null);
			$table->string('size')->nullable()->default(null);
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
