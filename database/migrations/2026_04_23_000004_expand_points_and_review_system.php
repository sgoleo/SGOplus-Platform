<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 擴充 Users 表點數
        Schema::table('users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('department');
        });

        // 2. 擴充 Tasks 表點數與狀態
        // 注意：SQLite 不支援直接修改 Enum，這裡我們直接新增 reward_points
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('reward_points')->default(0)->after('due_date');
        });

        // 3. 建立點數交易紀錄
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('points_awarded');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('reward_points');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
