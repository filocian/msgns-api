<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_prepaid_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prepaid_package_id')->constrained('prepaid_packages');
            $table->unsignedInteger('requests_remaining');
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('stripe_payment_intent_id')->unique();
            $table->timestamps();

            $table->index(['user_id', 'requests_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_prepaid_balances');
    }
};
