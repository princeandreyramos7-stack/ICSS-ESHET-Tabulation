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
     * Creates the initial administrator account from ADMIN_NAME, ADMIN_EMAIL
     * and ADMIN_PASSWORD in .env (read via config so a cached config works).
     * Runs safely more than once: an existing account is left untouched.
     */
    public function run(): void
    {
        $name = (string) config('conference.admin.name', 'Administrator');
        $email = (string) config('conference.admin.email', '');
        $password = (string) config('conference.admin.password', '');

        if (app()->isProduction()) {
            // Never create an administrator with a missing or guessable password in production.
            if ($email === '' || strlen($password) < 12) {
                throw new RuntimeException(
                    'Set ADMIN_EMAIL and an ADMIN_PASSWORD of at least 12 characters in .env, '
                    . 'run "php artisan optimize", then seed again.'
                );
            }
        } else {
            // Local fallbacks so a fresh clone can seed without editing .env.
            $email = $email !== '' ? $email : 'admin@conference.local';
            $password = $password !== '' ? $password : 'ChangeMe123!';
        }

        $adminRole = Role::firstOrCreate(['name' => User::ROLE_ADMIN]);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => Carbon::now(),
            ]
        );

        if (! $user->hasRole($adminRole)) {
            $user->assignRole($adminRole);
        }

        $this->command?->info("Admin account ready: {$email}");
    }
}
