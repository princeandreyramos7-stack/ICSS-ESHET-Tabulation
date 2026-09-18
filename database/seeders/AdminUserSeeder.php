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
     * Runs safely more than once: an existing account keeps its password,
     * unless that password is not a bcrypt hash (e.g. inserted by hand), in
     * which case it is reset to ADMIN_PASSWORD so the account can log in again.
     */
    public function run(): void
    {
        $name = (string) config('conference.admin.name', 'Administrator');
        $email = (string) config('conference.admin.email', '');
        $password = (string) config('conference.admin.password', '');

        if (app()->isProduction()) {
            // Never create an administrator with a missing or guessable password in production.
            if ($email === '' || strlen($password) < 8) {
                throw new RuntimeException(
                    'Set ADMIN_EMAIL and an ADMIN_PASSWORD of at least 8 characters (12+ recommended) in .env, '
                    . 'run "php artisan optimize", then seed again.'
                );
            }
        } else {
            // Local fallbacks so a fresh clone can seed without editing .env.
            $email = $email !== '' ? $email : 'piton@gmail.com';
            $password = $password !== '' ? $password : 'pitonadmin123';
        }

        $adminRole = Role::firstOrCreate(['name' => User::ROLE_ADMIN]);

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => Carbon::now(),
            ]);
            $user->save();
            $this->command?->info("Admin account created: {$email}");
        } elseif (! $this->isBcryptHash($user->password)) {
            // Laravel's BcryptHasher throws "This password does not use the Bcrypt
            // algorithm" (HTTP 500 on login) for plain-text or foreign hashes.
            $user->password = Hash::make($password);
            $user->save();
            $this->command?->warn("Admin password for {$email} was not a bcrypt hash; it has been reset to ADMIN_PASSWORD.");
        } else {
            $this->command?->info("Admin account ready: {$email}");
        }

        if (! $user->hasRole($adminRole)) {
            $user->assignRole($adminRole);
        }
    }

    /**
     * True when the stored value is a hash Laravel's default bcrypt hasher can verify.
     */
    protected function isBcryptHash(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (password_get_info($value)['algoName'] ?? null) === 'bcrypt';
    }
}
