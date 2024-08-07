<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'whatsapp_phones';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_id')->constrained('products');
			$table->string('phone', 20);
			$table->string('prefix', 10);
			$table->timestamps();

//			$table->unique(['phone', 'prefix']);
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
