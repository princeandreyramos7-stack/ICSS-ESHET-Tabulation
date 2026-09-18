<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config()->set('conference.admin', [
        'name' => 'Seeded Admin',
        'email' => 'seeded-admin@example.test',
        'password' => 'seeded-password-123',
    ]);
});

test('the admin seeder creates a bcrypt-hashed administrator that can log in', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'seeded-admin@example.test')->firstOrFail();

    expect(password_get_info($admin->password)['algoName'])->toBe('bcrypt')
        ->and(Hash::check('seeded-password-123', $admin->password))->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue();

    $this->post('/login', [
        'email' => 'seeded-admin@example.test',
        'password' => 'seeded-password-123',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

test('re-seeding leaves a healthy admin password untouched', function () {
    $this->seed(AdminUserSeeder::class);
    $before = User::where('email', 'seeded-admin@example.test')->value('password');

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'seeded-admin@example.test')->value('password'))->toBe($before)
        ->and(User::where('email', 'seeded-admin@example.test')->count())->toBe(1);
});

test('the admin seeder repairs a stored password that is not a bcrypt hash', function () {
    $this->seed(AdminUserSeeder::class);

    // Simulate a row edited by hand in phpMyAdmin: plain text instead of a hash.
    // Bypass Eloquent so the "hashed" cast does not fix it for us.
    DB::table('users')->where('email', 'seeded-admin@example.test')->update(['password' => 'plain-text-password']);

    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'seeded-admin@example.test')->firstOrFail();

    expect(password_get_info($admin->password)['algoName'])->toBe('bcrypt')
        ->and(Hash::check('seeded-password-123', $admin->password))->toBeTrue();

    // Logging in no longer throws "This password does not use the Bcrypt algorithm".
    $this->post('/login', [
        'email' => 'seeded-admin@example.test',
        'password' => 'seeded-password-123',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});
