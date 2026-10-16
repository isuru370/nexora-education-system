<?php

/*
|--------------------------------------------------------------------------
| Demo access (landing page "Open demo" button)
|--------------------------------------------------------------------------
|
| DEMO_URL       Where the button goes. Empty = this system's login page.
| DEMO_EMAIL     Optional. Shown on the landing page and filled into the
| DEMO_PASSWORD  login form when it is opened from the demo button.
|                Use a demo account with limited permissions.
|
*/

return [

    'url' => env('DEMO_URL'),

    'email' => env('DEMO_EMAIL'),

    'password' => env('DEMO_PASSWORD'),

];
