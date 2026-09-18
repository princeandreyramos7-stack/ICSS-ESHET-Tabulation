<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the initial administrator account.
     * Credentials come from .env (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD)
     * so production never ships with a known default password.
     */
    public function run(): void
    {
        // Never create an administrator with a guessable default password in production.
        if (app()->isProduction() && (! env('ADMIN_EMAIL') || ! env('ADMIN_PASSWORD') || strlen((string) env('ADMIN_PASSWORD')) < 12)) {
            throw new RuntimeException('Set ADMIN_EMAIL and a strong ADMIN_PASSWORD (12+ characters) in .env before seeding in production.');
        }

        $adminRole = Role::firstOrCreate(['name' => User::ROLE_ADMIN]);

        $email = env('ADMIN_EMAIL', 'admin@conference.local');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
                'email_verified_at' => Carbon::now(),
            ]
        );

        if (! $user->hasRole($adminRole)) {
            $user->assignRole($adminRole);
        }

        $this->command?->info("Admin account ready: {$email}");
    }
}
