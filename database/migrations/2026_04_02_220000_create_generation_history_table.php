<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('generation_history', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('generated_at')->comment('UTC timestamp of when the generation occurred');
            $table->unsignedInteger('total_count')->comment('Total number of products generated');
            $table->json('summary')->comment('Array of {type_code, type_name, quantity, size, description}');
            $table->binary('excel_blob')->nullable()->comment('Raw .xlsx binary content (MEDIUMBLOB)');
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('generated_at', 'idx_generation_history_generated_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE generation_history MODIFY excel_blob MEDIUMBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_history');
    }
};
