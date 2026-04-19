<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('subscription_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('mode', ['classic', 'prepaid']);
            $table->json('billing_periods')->nullable()->comment('Array of monthly|annual. NULL for prepaid.');
            $table->unsignedInteger('base_price_cents');
            $table->string('permission_name', 100)->unique();
            $table->unsignedInteger('google_review_limit')->default(0);
            $table->unsignedInteger('instagram_content_limit')->default(0);
            $table->string('stripe_product_id')->nullable();
            $table->json('stripe_price_ids')->nullable()->comment('Map of billing_period => stripe_price_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('mode');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_types');
    }
};
