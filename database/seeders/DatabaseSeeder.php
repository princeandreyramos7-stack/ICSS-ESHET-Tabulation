<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe seed: roles, the admin account, the six tracks and five criteria.
     * Evaluators and papers are created by the admin through the UI
     * (or run DemoSeeder for local sample data).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            TrackSeeder::class,
            CriterionSeeder::class,
        ]);
    }
}
