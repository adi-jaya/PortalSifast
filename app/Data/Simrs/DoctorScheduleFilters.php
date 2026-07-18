<?php

namespace App\Data\Simrs;

final readonly class DoctorScheduleFilters
{
    public function __construct(
        public ?string $simrsDay = null,
        public ?string $kdPoli = null,
        public ?string $poli = null,
        public ?string $q = null,
        public bool $withFoto = true,
        public bool $allDays = false,
    ) {}

    public function hasDayFilter(): bool
    {
        return ! $this->allDays && $this->simrsDay !== null;
    }
}
