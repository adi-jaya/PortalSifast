<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Awalan nomor inventaris portal
    |--------------------------------------------------------------------------
    */

    'awalan_kode' => env('ASET_AWALAN_KODE', 'INV'),

    /*
    |--------------------------------------------------------------------------
    | Kode ruang fallback bila aset SIMRS tanpa id_ruang
    |--------------------------------------------------------------------------
    */

    'kode_ruang_fallback' => env('ASET_KODE_RUANG_FALLBACK', 'TANPA'),

    'nama_ruang_fallback' => env('ASET_NAMA_RUANG_FALLBACK', 'Tanpa Ruang'),

];
