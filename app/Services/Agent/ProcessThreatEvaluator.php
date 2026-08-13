<?php

namespace App\Services\Agent;

class ProcessThreatEvaluator
{
    /**
     * @param  list<array<string, mixed>>  $candidates  Raw suspicious_processes from agent heartbeat.
     * @return list<array{pid: int, exe: string, path: string|null, cpu_percent: float|null, reasons: list<string>}>
     */
    public function evaluate(array $candidates): array
    {
        $allowlist = array_map(
            strtolower(...),
            config('agent.process_allowlist', []),
        );
        $minerPatterns = config('agent.miner_exe_patterns', []);
        $tempPathFragments = config('agent.suspicious_path_fragments', []);
        $cpuThreshold = (float) config('agent.suspicious_cpu_percent', 30);

        $seen = [];
        $out = [];

        foreach ($candidates as $row) {
            if (! is_array($row)) {
                continue;
            }

            $pid = (int) ($row['pid'] ?? 0);
            $exe = strtolower(trim((string) ($row['exe'] ?? '')));
            $path = isset($row['path']) ? trim((string) $row['path']) : null;
            $cpu = isset($row['cpu_percent']) ? (float) $row['cpu_percent'] : null;
            $agentReasons = $row['reasons'] ?? [];
            if (! is_array($agentReasons)) {
                $agentReasons = [];
            }

            if ($pid < 1 || $exe === '') {
                continue;
            }

            $key = "{$pid}:{$exe}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($this->isAllowlisted($exe, $allowlist)) {
                continue;
            }

            $reasons = [];

            foreach ($agentReasons as $reason) {
                if (is_string($reason) && $reason !== '') {
                    $reasons[] = $reason;
                }
            }

            if ($this->matchesMinerPattern($exe, $minerPatterns)) {
                $reasons[] = 'known_miner_name';
            }

            if ($path !== null && $path !== '' && $this->matchesSuspiciousPath($path, $tempPathFragments)) {
                $reasons[] = 'suspicious_path';
            }

            if ($cpu !== null && $cpu >= $cpuThreshold) {
                $reasons[] = 'high_cpu';
            }

            $reasons = array_values(array_unique($reasons));

            if ($reasons === []) {
                continue;
            }

            $out[] = [
                'pid' => $pid,
                'exe' => $exe,
                'path' => $path !== '' ? $path : null,
                'cpu_percent' => $cpu,
                'reasons' => $reasons,
            ];
        }

        usort($out, fn (array $a, array $b): int => ($b['cpu_percent'] ?? 0) <=> ($a['cpu_percent'] ?? 0));

        return $out;
    }

    /**
     * @param  list<string>  $allowlist
     */
    private function isAllowlisted(string $exe, array $allowlist): bool
    {
        foreach ($allowlist as $allowed) {
            if ($allowed === $exe) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesMinerPattern(string $exe, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = strtolower(trim($pattern));
            if ($pattern === '') {
                continue;
            }

            if (str_contains($exe, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $fragments
     */
    private function matchesSuspiciousPath(string $path, array $fragments): bool
    {
        $normalized = strtolower(str_replace('/', '\\', $path));

        foreach ($fragments as $fragment) {
            $fragment = strtolower(str_replace('/', '\\', trim($fragment)));
            if ($fragment !== '' && str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
