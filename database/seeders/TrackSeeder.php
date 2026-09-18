<?php

namespace Database\Seeders;

use App\Models\Track;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    /**
     * The six conference tracks, taken from the official evaluation sheets.
     */
    public function run(): void
    {
        $tracks = [
            1 => 'Sustainable Engineering Solutions / Renewable Energy and Environmental Technologies',
            2 => 'Bridging Technology and Public Health / Cross-Disciplinary Approaches to Global Health Challenges',
            3 => 'Human Arts and Architecture',
            4 => 'Computing Technology',
            5 => 'Digital Innovation in Education and Social Sciences',
            6 => 'Criminology and Legal Justice',
        ];

        foreach ($tracks as $number => $name) {
            Track::updateOrCreate(['number' => $number], ['name' => $name]);
        }

        $this->command?->info('Tracks seeded: ' . count($tracks));
    }
}
