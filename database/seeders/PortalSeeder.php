<?php

namespace Database\Seeders;

use App\Models\Portal;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $portals = [
            [
                'name' => 'SIRIKA (BKKBN)',
                'slug' => 'sirika-bkkbn',
                'category' => 'BKKBN',
                'url' => 'https://siga-sirika.bkkbn.go.id/login',
                'url_pattern' => '*://siga-sirika.bkkbn.go.id/*',
                'description' => 'Sistem Informasi Rekonsiliasi Intervensi Penurunan Stunting BKKBN',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_sirika',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#c', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'New SIGA (Kemendukbangga)',
                'slug' => 'siga-kemendukbangga',
                'category' => 'BKKBN',
                'url' => 'https://newsiga-siga.kemendukbangga.go.id/#/login',
                'url_pattern' => '*://newsiga-siga.kemendukbangga.go.id/*',
                'description' => 'Sistem Informasi Keluarga Kementerian Kependudukan dan Pembangunan Keluarga',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_siga',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#email', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'SIHA 2.1 (Kemenkes)',
                'slug' => 'siha-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://sihapims2.kemkes.go.id/login',
                'url_pattern' => '*://sihapims2.kemkes.go.id/*',
                'description' => 'Sistem Informasi HIV/AIDS dan IMS Kementerian Kesehatan RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_siha',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']", "input[name='user']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'MPDN (Kemenkes)',
                'slug' => 'mpdn-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://mpdn.kemkes.go.id/masuk',
                'url_pattern' => '*://mpdn.kemkes.go.id/*',
                'description' => 'Maternal Perinatal Death Notification Kementerian Kesehatan RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_mpdn',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']", "input[name='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 4,
            ],
            [
                'name' => 'SITB Jatim (Kemenkes)',
                'slug' => 'sitb-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://jatim.sitb.id/sitb2024/app',
                'url_pattern' => '*://jatim.sitb.id/*',
                'description' => 'Sistem Informasi Tuberkulosis Kementerian Kesehatan Jawa Timur',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_sitb',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[name='username']", '#username'],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 5,
            ],
            [
                'name' => 'SIGIZI Terpadu (Kemenkes)',
                'slug' => 'sigizi-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://sigizikesga-stg.kemkes.go.id',
                'url_pattern' => '*://sigizikesga-stg.kemkes.go.id/*',
                'description' => 'Sistem Informasi Gizi Terpadu Kesehatan Keluarga Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_sigizi',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 6,
            ],
            [
                'name' => 'SATU SEHAT Platform (Kemenkes)',
                'slug' => 'satusehat-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://satusehat.kemkes.go.id/platform/login',
                'url_pattern' => '*://satusehat.kemkes.go.id/*',
                'description' => 'Platform Integrasi Data Kesehatan SATU SEHAT Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_satusehat',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#email', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 7,
            ],
            [
                'name' => 'MutuFasyankes - IKP (Kemenkes)',
                'slug' => 'mutufasyankes-ikp',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/halaman/dashboard',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Pelaporan Insiden Keselamatan Pasien (IKP) Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_ikp',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#user', "input[name='username']", "input[name='user']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#pass', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 8,
            ],
            [
                'name' => 'MutuFasyankes - PPRA (Kemenkes)',
                'slug' => 'mutufasyankes-ppra',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/ppra/',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Program Pengendalian Resistensi Antimikroba (PPRA) Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_ppra',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[name='username']", '#username'],
                    ],
                    'password_field' => [
                        'selectors' => ["input[name='password']", '#password', "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 9,
            ],
            [
                'name' => 'MutuFasyankes - SIMAR (Kemenkes)',
                'slug' => 'mutufasyankes-simar',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/simar/',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Sistem Informasi Manajemen Akreditasi Rumah Sakit (SIMAR)',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_simar',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#uname', "input[name='username']", "input[name='uname']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#pwd', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 10,
            ],
            [
                'name' => 'SIRS Online (Yankes Kemenkes)',
                'slug' => 'sirs-online',
                'category' => 'Kemenkes',
                'url' => 'https://akun-yankes.kemkes.go.id/',
                'url_pattern' => '*://akun-yankes.kemkes.go.id/*',
                'description' => 'Sistem Informasi Rumah Sakit Online Kementerian Kesehatan RI',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_sirs',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[type='email']", "input[type='text']", "input[placeholder*='email' i]"],
                    ],
                    'password_field' => [
                        'selectors' => ["input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 11,
            ],
        ];

        foreach ($portals as $portal) {
            Portal::firstOrCreate(
                ['slug' => $portal['slug']],
                $portal
            );
        }
    }
}
