<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate([
            'name' => 'ouvrier',
            'guard_name' => 'web',
        ]);
    }

    public function down(): void
    {
        Role::query()
            ->where('name', 'ouvrier')
            ->where('guard_name', 'web')
            ->delete();
    }
};
