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

    /*
    |--------------------------------------------------------------------------
    | Default penyusutan aset (bisa diubah lewat UI /aset/pengaturan-penyusutan)
    |--------------------------------------------------------------------------
    */

    'penyusutan' => [
        'metode' => 'garis_lurus',
        'residu_persen_default' => (float) env('ASET_RESIDU_PERSEN_DEFAULT', 1),
        'umur_bulan_medis' => (int) env('ASET_UMUR_BULAN_MEDIS', 60),
        'umur_bulan_non_medis' => (int) env('ASET_UMUR_BULAN_NON_MEDIS', 48),
        'umur_bulan_default' => (int) env('ASET_UMUR_BULAN_DEFAULT', 60),
    ],

];
