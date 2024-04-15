<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $table = 'client_products';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
//        Schema::create($this->table, function (Blueprint $table) {
//            $table->id();
//            $table->foreignId('user_id')->constrained();
//            $table->string('family')->index();
//            $table->foreignId('product_type_id')->constrained();
//            $table->foreignId('product_id');
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
