<?php

/*
|--------------------------------------------------------------------------
| Database backup / restore client paths
|--------------------------------------------------------------------------
|
| Read through config() so they still work after `php artisan config:cache`
| (env() returns null outside config files once the config is cached).
| Empty = Windows WAMP default or `mysqldump` / `mysql` on the PATH.
|
*/

return [

    'mysqldump_path' => env('MYSQLDUMP_PATH'),

    'mysql_client_path' => env('MYSQL_CLIENT_PATH'),

];
