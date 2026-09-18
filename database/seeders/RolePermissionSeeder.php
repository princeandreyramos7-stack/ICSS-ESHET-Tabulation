<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => User::ROLE_ADMIN]);
        Role::firstOrCreate(['name' => User::ROLE_EVALUATOR]);

        $this->command?->info('Roles seeded: admin, evaluator');
    }
}
