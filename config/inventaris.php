<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SIMRS Inventaris Asset Base URL
    |--------------------------------------------------------------------------
    |
    | Relative paths in inventaris_gambar.photo (e.g. pages/upload/cmm.jpg)
    | are fetched server-side from this base URL and returned as data URIs
    | so the browser never needs the internal SIMRS host.
    |
    */

    'asset_base_url' => rtrim((string) env(
        'SIMRS_INVENTARIS_ASSET_BASE_URL',
        'http://192.168.10.3/webapps2/inventaris'
    ), '/'),

];
