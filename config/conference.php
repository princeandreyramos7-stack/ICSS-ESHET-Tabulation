<?php

/*
|--------------------------------------------------------------------------
| Conference branding
|--------------------------------------------------------------------------
| Shown on the welcome page, login page, sidebar, and every printable
| result sheet. Override any value in .env without touching code.
*/

return [
    'acronym' => env('CONFERENCE_ACRONYM', 'ICSS-ESHET 2026'),
    'edition' => env('CONFERENCE_EDITION', '2nd International Conference'),
    'name' => env('CONFERENCE_NAME', '2nd International Conference on Sustainable Solutions in Engineering, Science, Health, Education, and Technology'),
    'short_name' => env('CONFERENCE_SHORT_NAME', 'ICSS-ESHET Tabulation'),
    'theme' => env('CONFERENCE_THEME', 'Research and Extension'),
    'tagline' => env('CONFERENCE_TAGLINE', 'Integrated Sustainable Systems: Advancing Global Sustainability Through Multidisciplinary Innovation Across Disciplines.'),
    'organizer' => env('CONFERENCE_ORGANIZER', 'Isabela State University'),
    'campus' => env('CONFERENCE_CAMPUS', 'City of Ilagan Campus'),
    'dates' => env('CONFERENCE_DATES', 'September 23-25, 2026'),
    // ISO date used for the countdown on the welcome page.
    'starts_at' => env('CONFERENCE_STARTS_AT', '2026-09-23T08:00:00+08:00'),
    'venue' => env('CONFERENCE_VENUE', 'City of Ilagan, Isabela'),
    'format' => env('CONFERENCE_FORMAT', 'Physical and Online'),
    'website' => env('CONFERENCE_WEBSITE', 'https://rdet-isu-ilagan.net/index'),
    'email' => env('CONFERENCE_EMAIL', 'research.ilagan@isu.edu.ph'),
    'phone' => env('CONFERENCE_PHONE', '+63 949 597 4328'),
    'year' => env('CONFERENCE_YEAR', '2026'),

    // Organization that built the system; shown as a "Developed by" credit on public pages.
    'developer' => [
        'name' => env('DEVELOPER_NAME', 'Philippine Information Technology of the North'),
        'short_name' => env('DEVELOPER_SHORT_NAME', 'PITON'),
        'tagline' => env('DEVELOPER_TAGLINE', 'Est. 2007'),
        'url' => env('DEVELOPER_URL', ''),
        'logo' => env('DEVELOPER_LOGO', '/img/piton-logo.png'),
    ],
];
