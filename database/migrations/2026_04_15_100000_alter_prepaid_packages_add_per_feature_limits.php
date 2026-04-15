<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prepaid_packages', function (Blueprint $table): void {
            $table->unsignedInteger('google_review_limit')->default(0)->after('permission_name');
            $table->unsignedInteger('instagram_content_limit')->default(0)->after('google_review_limit');
            $table->dropColumn('requests_included');
        });
    }

    public function down(): void
    {
        Schema::table('prepaid_packages', function (Blueprint $table): void {
            $table->unsignedInteger('requests_included')->default(0)->after('permission_name');
            $table->dropColumn(['google_review_limit', 'instagram_content_limit']);
        });
    }
};
