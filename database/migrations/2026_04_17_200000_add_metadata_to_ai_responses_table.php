<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ai_responses', 'metadata')) {
            return;
        }

        Schema::table('ai_responses', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('system_prompt_snapshot');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ai_responses', 'metadata')) {
            return;
        }

        Schema::table('ai_responses', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
