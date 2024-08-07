<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	protected string $table = 'whatsapp_messages';
    /**
     * Run the migrations.
     */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_id')->constrained('products');
			$table->foreignId('phone_id')->constrained('whatsapp_phones');
			$table->foreignId('locale_id')->constrained('whatsapp_locales');
			$table->text('message');
			$table->boolean('default')->default(false);
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
