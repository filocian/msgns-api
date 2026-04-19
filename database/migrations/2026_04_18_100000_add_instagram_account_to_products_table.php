<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'instagram_account_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->string('instagram_account_id', 255)->nullable()->after('target_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'instagram_account_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('instagram_account_id');
        });
    }
};
