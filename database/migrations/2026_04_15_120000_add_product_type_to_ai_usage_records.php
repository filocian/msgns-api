<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_records', function (Blueprint $table): void {
            $table->enum('product_type', ['google_reviews', 'instagram'])->after('source');
            // Drop existing index and recreate with product_type included
            $table->dropIndex(['user_id', 'source', 'used_at']);
            $table->index(['user_id', 'source', 'product_type', 'used_at'], 'aur_user_source_product_used_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_records', function (Blueprint $table): void {
            $table->dropIndex('aur_user_source_product_used_idx');
            $table->dropColumn('product_type');
            $table->index(['user_id', 'source', 'used_at']);
        });
    }
};
