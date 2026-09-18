<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe seed: roles, the admin account, the six tracks and five criteria.
     * Evaluators and papers are created by the admin through the UI
     * (there is no demo seeder; use the Evaluators and Papers pages).
     */
    public function run(): void
    {
        // Step 1: Create roles (admin and evaluator)
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Step 2: Create the initial administrator account
        $this->seedAdminUser();

        // Step 3: Seed conference-specific data
        $this->call([
            TrackSeeder::class,
            CriterionSeeder::class,
        ]);
    }

    /**
     * Create or update the initial administrator account.
     * 
     * Email: Piton@gmail.com
     * Password: piton_admin@2025
     * 
     * Safe to run multiple times (idempotent).
     */
    private function seedAdminUser(): void
    {
        $email = 'Piton@gmail.com';
        $password = 'piton_admin@2025';
        $name = 'Administrator';

        // Ensure the admin role exists (created by RolePermissionSeeder)
        $adminRole = Role::where('name', User::ROLE_ADMIN)->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Ensure RolePermissionSeeder runs first.');
            return;
        }

        // Create or update the admin user
        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => Carbon::now(),
            ]
        );

        // Assign admin role if not already assigned
        if (!$admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }

        // Output credentials for convenience (especially during development)
        $this->command->info("✓ Admin account ready");
        $this->command->info("  Email: {$email}");
        $this->command->info("  Password: {$password}");
        $this->command->warn("  ⚠ Change the password after first login!");
    }
}
