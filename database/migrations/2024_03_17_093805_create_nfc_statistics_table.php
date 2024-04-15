<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $table = 'nfc_statistics';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
//        Schema::create($this->table, function (Blueprint $table) {
//            $table->id();
//            $table->foreignId('nfc_id')->constrained();
//            $table->string('os', length: 20);
//            $table->string('browser', length: 100);
//            $table->string('locale', length: 5);
//            $table->text('user_agent');
//            $table->string('action', length: 20);
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
