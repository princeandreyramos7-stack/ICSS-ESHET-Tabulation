<?php

use App\Models\User;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('the welcome page renders for guests with tracks, criteria and branding', function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('tracks', 7)
            ->has('criteria', 5)
            ->where('criteria.0.weight', 25)
            ->has('conference.acronym')
            ->has('conference.starts_at')
            ->where('auth.user', null));
});

test('signed-in users are sent from the welcome page to their dashboard', function () {
    $this->seed([RolePermissionSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));
});
