<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $user = User::where('name', 'TEST')->first();
        if ($user) {
            $user->syncRoles(['SGOstudio-Member']);
        }
    }
    public function down(): void {}
};
