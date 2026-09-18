<?php

namespace Database\Seeders;

use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Sample evaluators and papers for local testing only.
 * Not called by DatabaseSeeder. Run manually:
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder creates sample accounts with a known password and must not run in production.');
        }

        $role = Role::firstOrCreate(['name' => User::ROLE_EVALUATOR]);

        for ($i = 1; $i <= 3; $i++) {
            $user = User::firstOrCreate(
                ['email' => "evaluator{$i}@conference.local"],
                [
                    'name' => "Evaluator {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => Carbon::now(),
                ]
            );
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        $samples = [
            1 => ['Solar-Powered Water Purification for Coastal Barangays', 'Micro-Hydro Feasibility in Upland Isabela'],
            2 => ['Mobile Health Screening for Rural Communities', 'AI-Assisted Triage in Provincial Hospitals'],
            3 => ['Vernacular Architecture of the Cagayan Valley', 'Adaptive Reuse of Heritage Structures'],
            4 => ['Lightweight IoT Framework for Smart Farming', 'Federated Learning on Edge Devices'],
            5 => ['Gamified Learning Modules for Senior High', 'Digital Literacy Among Rural Educators'],
            6 => ['Community Policing and Crime Reporting Apps', 'Restorative Justice Outcomes in Barangay Courts'],
        ];

        foreach ($samples as $trackNumber => $titles) {
            $track = Track::where('number', $trackNumber)->first();
            if (! $track) {
                continue;
            }
            foreach ($titles as $index => $title) {
                $paperNo = sprintf('T%d-%03d', $trackNumber, $index + 1);
                Paper::firstOrCreate(
                    ['paper_no' => $paperNo],
                    [
                        'track_id' => $track->id,
                        'title' => $title,
                        'researcher' => fake()->name(),
                        'affiliation' => fake()->company(),
                        'presentation_order' => $index + 1,
                    ]
                );
            }
        }

        $this->command?->info('Demo data seeded: 3 evaluators (password: password), 12 papers.');
    }
}
