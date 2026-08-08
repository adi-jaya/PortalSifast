<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentApiKeyService
{
    public function generatePlainKey(): string
    {
        return 'rsag_'.Str::random(40);
    }

    public function prefix(string $plainKey): string
    {
        return substr($plainKey, 0, 8);
    }

    public function hash(string $plainKey): string
    {
        return Hash::make($plainKey);
    }

    public function matches(string $plainKey, string $hash): bool
    {
        return Hash::check($plainKey, $hash);
    }
}
