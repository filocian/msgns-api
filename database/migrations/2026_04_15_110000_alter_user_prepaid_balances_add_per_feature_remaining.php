<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->unsignedInteger('google_review_requests_remaining')->default(0)->after('prepaid_package_id');
            $table->unsignedInteger('instagram_requests_remaining')->default(0)->after('google_review_requests_remaining');
        });

        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->index(['user_id', 'google_review_requests_remaining', 'instagram_requests_remaining'], 'upb_user_per_feature_idx');
        });

        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'requests_remaining']);
            $table->dropColumn('requests_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->unsignedInteger('requests_remaining')->default(0)->after('prepaid_package_id');
        });

        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->index(['user_id', 'requests_remaining']);
        });

        Schema::table('user_prepaid_balances', function (Blueprint $table): void {
            $table->dropIndex('upb_user_per_feature_idx');
            $table->dropColumn(['google_review_requests_remaining', 'instagram_requests_remaining']);
        });
    }
};
