<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 擴充 Tasks 表，支援公海徵集
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_crowdsourced')->default(false)->after('status');
            $table->integer('max_assignees')->default(1)->after('is_crowdsourced');
        });

        // 2. 擴充 task_user 中介表，支援個人進度與證明
        // 注意：SQLite 不支援在現有表新增並設定外鍵，但我們目前的中介表很單純，直接修改。
        Schema::table('task_user', function (Blueprint $table) {
            $table->string('status')->default('in_progress')->after('user_id');
            $table->string('evidence_image_path')->nullable()->after('status');
            $table->text('evidence_text')->nullable()->after('evidence_image_path');
            $table->integer('points_awarded')->default(0)->after('evidence_text');
        });
    }

    public function down(): void
    {
        Schema::table('task_user', function (Blueprint $table) {
            $table->dropColumn(['status', 'evidence_image_path', 'evidence_text', 'points_awarded']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['is_crowdsourced', 'max_assignees']);
        });
    }
};
