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
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->foreignId('product_type_id')->constrained('product_types');
			$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
			$table->string('model');
			$table->unsignedBigInteger('linked_to_product_id')->nullable()->unique();
			$table->string('password');
			$table->text('target_url')->nullable();
			$table->integer('usage')->default(0);
			$table->string('name', length: 150)->default('');
			$table->text('description')->nullable();
			$table->boolean('active')->default(false);
			$table->timestamps();

			$table->foreign('linked_to_product_id')
				->references('id')
				->on($this->table)
				->onDelete('cascade')
				->onUpdate('cascade');
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
