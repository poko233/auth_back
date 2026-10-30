<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table): void {
            $table->foreignId('test_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->jsonb('resources')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('test_user_id');
            $table->dropColumn('resources');
        });
    }
};
