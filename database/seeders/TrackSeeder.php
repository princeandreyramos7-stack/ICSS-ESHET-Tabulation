<?php

namespace Database\Seeders;

use App\Models\Track;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    /**
     * The seven parallel-session tracks (Day 02, September 24, 2026), with the
     * venue, session chair and co-session chair from the official programme.
     * Keyed by track number, so re-seeding updates names and rooms in place.
     */
    public function run(): void
    {
        $tracks = [
            1 => [
                'name' => 'Sustainable Engineering Solutions / Renewable Energy and Environmental Technologies',
                'venue' => 'Room 201, New CEAT Building',
                'session_chair' => 'Dr. Orfel L. Bejarin',
                'co_session_chair' => 'Prof. Jaydwin T. Labiano',
            ],
            2 => [
                'name' => 'Bridging Technology and Public Health / Cross-Disciplinary Approaches to Global Health Challenges',
                'venue' => 'CON Simulation Room, Allied Health Building',
                'session_chair' => 'Dr. Jolo R. Galabay',
                'co_session_chair' => 'Prof. Beverly Taguinod',
            ],
            3 => [
                'name' => 'Digital Innovations in Education and Social Sciences',
                'venue' => 'Room 202, New CEAT Building',
                'session_chair' => 'Dr. Geraldine J. Paguigan',
                'co_session_chair' => 'Dr. Aisie O. Bete-Liban',
            ],
            4 => [
                'name' => 'Computing Technology',
                'venue' => 'Room 203, New CEAT Building',
                'session_chair' => 'Dr. Crestian A. Agustin',
                'co_session_chair' => 'Dr. Michelle G. Quijano',
            ],
            5 => [
                'name' => 'Cross-Disciplinary in Legal Justice, Human Arts and Architecture',
                'venue' => 'Room 301, New CEAT Building',
                'session_chair' => 'Dr. Zach Chamberlaine M. Corpuz',
                'co_session_chair' => 'Prof. Joey G. Natividad',
            ],
            6 => [
                'name' => 'Extension Track - Human Development, Sustainable Agriculture, Health and Environmental Resilience',
                'venue' => 'SOM Simulation Room, Allied Health Building',
                'session_chair' => 'Prof. Ryan M. Amigo',
                'co_session_chair' => 'Dr. Bagnos A. Quebral, Jr.',
            ],
            7 => [
                'name' => 'Extension Track - Engineering, Smart Analytics, ICT and Digital Innovations',
                'venue' => 'SOM Amphitheater, Allied Health Building',
                'session_chair' => 'Dr. Ronald B. Rivera',
                'co_session_chair' => 'Dr. Ma. Cristina Lalaine M. Nerona',
            ],
        ];

        foreach ($tracks as $number => $attributes) {
            Track::updateOrCreate(['number' => $number], $attributes);
        }

        $this->command?->info('Tracks seeded: ' . count($tracks));
    }
}
