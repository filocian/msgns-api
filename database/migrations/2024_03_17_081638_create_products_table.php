<?php

use App\Static\Config;
use App\Static\StaticProductType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $table = 'products';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->charset(Config::DB_CHARSET);
            $table->collation = Config::DB_COLLATION;

            $table->id();
            $table->string('name', length: 150)->default('');
            $table->string('type', length: 15)->default(StaticProductType::NFC);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->text('description')->nullable();
            $table->integer('qty')->nullable();
            $table->string('tags')->nullable();
            $table->string('admin_tags')->nullable();
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
