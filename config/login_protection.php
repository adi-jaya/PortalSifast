<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progressive Login Protection
    |--------------------------------------------------------------------------
    |
    | After max_attempts failed logins, the account+IP is locked with a
    | progressive cooldown (lockout_seconds). Warnings start at warn_after.
    |
    */

    'max_attempts' => 5,

    'warn_after' => 3,

    /*
    | Seconds to wait after each lockout cycle (1st lockout, 2nd, …).
    | The last value is reused for further lockouts.
    */
    'lockout_seconds' => [120, 300, 600, 1200, 1800],

];
