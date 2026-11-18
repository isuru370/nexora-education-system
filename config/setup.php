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

    /*
    | The user PHP runs as. nexora:deploy gives storage/ and bootstrap/cache/
    | back to this user when it is run as root.
    */
    'web_user' => env('WEB_USER', 'www-data'),

];
