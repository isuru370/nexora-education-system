<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Require a login token on the parent API (/api/parent/v1/...)
    |--------------------------------------------------------------------------
    |
    | /auth/login returns data.token. Send it as "Authorization: Bearer <token>"
    | on every other request; the student is then taken from the token and any
    | student_id in the body is ignored.
    |
    | false (default for now): requests without a token still work, so older
    | parent apps keep working. Turn this on once every app sends the token:
    |
    |     PARENT_API_REQUIRE_TOKEN=true
    |
    */

    'require_token' => (bool) env('PARENT_API_REQUIRE_TOKEN', false),

    /*
    | A token stops working after this many days without being used.
    */
    'token_days' => (int) env('PARENT_API_TOKEN_DAYS', 90),

    /*
    | Wrong-password attempts allowed per minute for one username.
    */
    'login_attempts_per_minute' => (int) env('PARENT_API_LOGIN_ATTEMPTS', 10),

];
