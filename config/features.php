<?php

/*
|--------------------------------------------------------------------------
| Features that can be switched on per institute
|--------------------------------------------------------------------------
|
| Every institute runs the same code. A feature listed here is OFF until it
| is switched on in that institute's database, on its server:
|
|   php artisan nexora:feature list
|   php artisan nexora:feature courses on
|   php artisan nexora:feature courses off
|
| In code:
|   feature('courses')                       PHP
|   @feature('courses') ... @endfeature      Blade
|   ->middleware('feature:courses')          routes (404 when off)
|
| Mobile apps read the list from GET /api/features (also in login and /api/me).
|
*/

return [

    'courses' => [
        'name' => 'Courses',
        'description' => 'Course management (diploma / certificate courses).',
    ],

    // Mobile app: after a QR attendance scan the phone says the student's
    // name, subject and grade (English) and this month's fee (Sinhala).
    'tts' => [
        'name' => 'Voice on attendance (TTS)',
        'description' => 'Mobile app speaks the name, subject, grade and "ගෙවා ඇත / ගෙවා නැත" after an attendance scan.',
    ],

    // Admin takes a monthly welfare amount from each teacher's salary (per
    // branch). It is the branch's welfare fund (not institute income); the
    // admin records what is paid out of it. Shown on the salary slip.
    'welfare' => [
        'name' => 'Teacher welfare',
        'description' => 'Monthly welfare taken from teacher salaries (per branch), welfare fund and its expenses.',
    ],

    // Only edu.nexorait.lk (the product site). Institutes: "/" goes to login.
    'landing_page' => [
        'name' => 'Landing page',
        'description' => 'NexoraIT EDU product page on "/" (welcome.blade.php). Off = "/" opens the login page.',
    ],

];
