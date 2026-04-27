<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('department')->comment('權限敘述');
            }
            if (!Schema::hasColumn('roles', 'icon')) {
                $table->string('icon')->nullable()->after('description')->comment('圖標');
            }
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color')->nullable()->after('icon')->comment('顏色漸層');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'color']);
        });
    }
};
