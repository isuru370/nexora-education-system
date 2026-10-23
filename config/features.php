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

];
