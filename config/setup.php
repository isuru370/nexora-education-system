<?php

/*
|--------------------------------------------------------------------------
| First install
|--------------------------------------------------------------------------
|
| Super admin account created by `php artisan db:seed` / `nexora:deploy`.
| Read through config() so seeding still works after config:cache.
|
*/

return [

    'super_admin' => [
        'username' => env('SUPER_ADMIN_USERNAME'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
