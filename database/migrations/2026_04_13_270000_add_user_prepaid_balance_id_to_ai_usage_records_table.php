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
            $table->foreignId('user_prepaid_balance_id')
                ->nullable()
                ->after('user_subscription_id')
                ->constrained('user_prepaid_balances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_records', function (Blueprint $table): void {
            $table->dropForeign(['user_prepaid_balance_id']);
            $table->dropColumn('user_prepaid_balance_id');
        });
    }
};
