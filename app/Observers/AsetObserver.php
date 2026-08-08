<?php

namespace App\Observers;

use App\Models\Aset;
use App\Models\AsetBarang;

class AsetObserver
{
    public function saved(Aset $aset): void
    {
        $this->syncJumlah($aset);
    }

    public function deleted(Aset $aset): void
    {
        $this->syncJumlah($aset);
    }

    public function restored(Aset $aset): void
    {
        $this->syncJumlah($aset);
    }

    private function syncJumlah(Aset $aset): void
    {
        if ($aset->aset_barang_id) {
            AsetBarang::hitungUlangJumlah($aset->aset_barang_id);
        }
    }
}
