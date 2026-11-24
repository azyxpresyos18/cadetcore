<?php

return [
    // Serial number format used when adding cadets / officers (e.g. CE-R25-002444).
    'serial_pattern' => '/^CE-[A-Z]\d{2}-\d{6}$/',

    // Cadet types offered in the Add New Cadet dialog (stored in cadet_types.type_name).
    'cadet_types' => ['Regular Cadet', 'Aspiring Cadet'],

    // Course abbreviations that older records may hold, and the full name they are expanded to by the
    // 2026_11_25_000002 migration. Add your own codes here before running `php artisan migrate`.
    'course_names' => [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSIS' => 'Bachelor of Science in Information Systems',
        'BSCS' => 'Bachelor of Science in Computer Science',
    ],

    // Year level at which a cadet counts as graduating during the yearly update.
    'final_year' => 4,

    // Unit structure created by `php artisan cadetcore:structure` (safe to run again; it only adds what is missing).
    // Every battalion gets every company, and every company gets every platoon listed here.
    'structure' => [
        'battalions' => ['1st Battalion', '2nd Battalion', '3rd Battalion'],
        'companies'  => ['Alpha Company', 'Bravo Company'],
        'platoons'   => ['1st Platoon', '2nd Platoon', '3rd Platoon'],
    ],

    // Standing labels shown on Cadet Records, from a cadet's net merit/demerit points.
    'standing' => [
        'good_min'        => 0,    // net >= 0   -> Good Standing
        'probation_below' => -10,  // net > -10  -> Warning, otherwise Probation
    ],

    // Rank given to a newly appointed officer, by position (matched against ranks.rank_name).
    'officer_rank' => [
        'adjutant'       => 'Corps Adjutant',
        'corps_s1'       => 'Corps S1 Personnel Officer',
        'battalion_s1'   => 'Battalion S1 Officer',
        'platoon_leader' => 'Platoon Leader',
    ],

    // Official offense list. The server is the source of truth for demerit points.
    'offenses' => [
        'Late' => 3, 'No ball cap' => 3, 'Not prescribed haircut (Male)' => 3, 'Not prescribed hairdo (Female)' => 3,
        'Dirty face' => 2, 'Unshaved mustache (Male)' => 3, 'Unnecessary accessories' => 3, 'Not prescribed T-shirt' => 3,
        'Long fingernails' => 3, 'Dirty fingernails' => 2, 'No belt and buckle' => 3, 'Not prescribed belt and buckle' => 3,
        'Not prescribed pants' => 3, 'White socks' => 3, 'Not prescribed shoes' => 3, 'Dangling shoelace' => 3,
        'Improper placement of shoelaces' => 2, 'Improper folding of handkerchief' => 2, 'Cramped handkerchief' => 2,
    ],
];
