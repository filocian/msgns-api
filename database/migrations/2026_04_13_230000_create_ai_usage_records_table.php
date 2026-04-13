<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_usage_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['free', 'classic', 'prepaid']);
            $table->foreignId('user_subscription_id')
                ->nullable()
                ->constrained('user_subscriptions')
                ->nullOnDelete();
            $table->timestamp('used_at');
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_records');
    }
};
