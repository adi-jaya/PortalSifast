<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class SimrsInventarisWriteGuard
{
    public static function reject(?string $message = null): RedirectResponse
    {
        return back()->withErrors([
            'simrs' => $message ?? 'Penulisan ke SIMRS dinonaktifkan. Gunakan modul Aset portal.',
        ]);
    }
}
