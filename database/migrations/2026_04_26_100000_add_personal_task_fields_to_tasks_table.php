<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('type')->default('official')->after('status')->comment('任務類型: official, personal');
            $table->foreignId('creator_id')->nullable()->after('type')->constrained('users')->onDelete('cascade')->comment('建立者 ID (主要用於個人任務)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['type', 'creator_id']);
        });
    }
};
