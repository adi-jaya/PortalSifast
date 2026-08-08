<?php

namespace App\Jobs;

use App\Services\Inventaris\SinkronAsetDariSimrs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class JalankanSinkronAsetJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $dipicuOleh = null) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('aset-sinkron-dari-simrs'))->expireAfter(600),
        ];
    }

    public function handle(SinkronAsetDariSimrs $sinkron): void
    {
        $sinkron->apply($this->dipicuOleh);
    }
}
