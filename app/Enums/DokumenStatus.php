<?php

namespace App\Enums;

enum DokumenStatus: string
{
    case Draft = 'draft';
    case ReviewUnit = 'review_unit';
    case ReviewMutu = 'review_mutu';
    case MenungguTte = 'menunggu_tte';
    case Aktif = 'aktif';
    case MenungguReview = 'menunggu_review';
    case Kadaluarsa = 'kadaluarsa';
    case Arsip = 'arsip';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::ReviewUnit => 'Review Unit',
            self::ReviewMutu => 'Review Mutu',
            self::MenungguTte => 'Menunggu Persetujuan Direktur',
            self::Aktif => 'Aktif',
            self::MenungguReview => 'Menunggu Review',
            self::Kadaluarsa => 'Kadaluarsa',
            self::Arsip => 'Arsip',
            self::Batal => 'Batal',
        };
    }

    /**
     * @return list<DokumenStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::MenungguTte, self::Batal],
            // Legacy — dokumen lama yang masih di tahap review
            self::ReviewUnit, self::ReviewMutu => [self::MenungguTte, self::Draft],
            self::MenungguTte => [self::Aktif, self::Draft],
            self::Aktif => [self::MenungguReview, self::Arsip],
            self::MenungguReview => [self::Aktif, self::Kadaluarsa],
            self::Kadaluarsa => [self::Arsip],
            self::Arsip, self::Batal => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
