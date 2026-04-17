<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds unique index on subscription_types.stripe_product_id to enforce one-to-one Stripe binding (issue #105). */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('subscription_types', function (Blueprint $table): void {
            $table->unique('stripe_product_id', 'subscription_types_stripe_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_types', function (Blueprint $table): void {
            $table->dropUnique('subscription_types_stripe_product_id_unique');
        });
    }
};
