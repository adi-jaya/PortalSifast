<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Agent enrollment key
    |--------------------------------------------------------------------------
    |
    | Shared secret required on POST /api/agent/register. Generate a long
    | random string for production and set AGENT_ENROLLMENT_KEY in .env.
    |
    */

    'enrollment_key' => env('AGENT_ENROLLMENT_KEY', 'change-me-agent-enrollment'),

    'offline_after_minutes' => (int) env('AGENT_OFFLINE_AFTER_MINUTES', 5),

    'default_interval' => (int) env('AGENT_DEFAULT_INTERVAL', 30),

];
