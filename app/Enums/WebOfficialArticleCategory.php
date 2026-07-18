<?php

namespace App\Enums;

enum WebOfficialArticleCategory: string
{
    case Promo = 'Promo';
    case Berita = 'Berita';
    case ArtikelKesehatan = 'Artikel Kesehatan';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
