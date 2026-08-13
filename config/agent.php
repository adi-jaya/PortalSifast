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

    'default_interval' => (int) env('AGENT_DEFAULT_INTERVAL', 10),

    'command_batch_size' => (int) env('AGENT_COMMAND_BATCH_SIZE', 5),

    /*
    |--------------------------------------------------------------------------
    | Process security (Windows agent ≥ 0.4.0)
    |--------------------------------------------------------------------------
    */

    'suspicious_cpu_percent' => (float) env('AGENT_SUSPICIOUS_CPU_PERCENT', 30),

    'process_allowlist' => [
        'chrome.exe',
        'msedge.exe',
        'firefox.exe',
        'cursor.exe',
        'code.exe',
        'explorer.exe',
        'dwm.exe',
        'searchhost.exe',
        'shellexperiencehost.exe',
        'startmenuexperiencehost.exe',
        'applicationframehost.exe',
        'runtimebroker.exe',
        'svchost.exe',
        'taskmgr.exe',
        'teams.exe',
        'outlook.exe',
        'winword.exe',
        'excel.exe',
        'powerpnt.exe',
        'onedrive.exe',
        'githubdesktop.exe',
        'anydesk.exe',
        'rs-agent.exe',
    ],

    'miner_exe_patterns' => [
        'xmrig',
        'minergate',
        'nicehash',
        'ethminer',
        'cpuminer',
        'miner.exe',
        'nscpu',
        'coinhive',
        'cryptonight',
    ],

    'suspicious_path_fragments' => [
        '\\appdata\\local\\temp\\',
        '\\windows\\temp\\',
        '\\programdata\\',
        '\\downloads\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric sample retention (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Heartbeats insert one row per device every ~30s. At ~90 hosts that is
    | ~260k rows/day. Prune daily via monitoring:prune-metric-samples.
    |
    */

    'metric_retention_days' => (int) env('AGENT_METRIC_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Monitorable asset categories (Phase 2.3)
    |--------------------------------------------------------------------------
    |
    | Default allowlist for inventaris ↔ agent linking. Override via UI at
    | /monitoring/pengaturan-kategori (stored in aset_pengaturan).
    |
    */

    'monitorable_kategori_codes' => [
        'KI005',  // Komputer (termasuk laptop/notebook)
        'KI012',  // Personal Computer
        'MINIPC', // Mini PC
        'TAB',    // Tablet
    ],

];
