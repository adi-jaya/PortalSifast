<?php

namespace App\Support;

/**
 * Copy roasting untuk laporan harian IT (semangat lewat candaan).
 */
final class DailyItTicketReportCopy
{
    /**
     * @param  list<array{assignee_id: int, name: string, count: int}>  $rows
     */
    public static function buildBody(array $rows, int $dayOfYear): string
    {
        if ($rows === []) {
            return self::pick([
                '😴 IT mengantuk — tidak ada pekerjaan yang di-assign hari ini.',
                '🛋️ Antrian tiket kosong. Mode healing, tapi jangan kebanyakan healing.',
                '🦗 Suara jangkrik di grup IT. Tidak ada assign hari ini — siap-siap diserang besok.',
            ], $dayOfYear);
        }

        $lines = [];
        foreach ($rows as $index => $row) {
            $lines[] = $row['name'].' — '.$row['count'].' tiket · '.self::technicianRoast($row['count'], $dayOfYear + $index);
        }

        $total = array_sum(array_column($rows, 'count'));
        $topName = $rows[0]['name'];

        $lines[] = '';
        $lines[] = 'Total: '.$total.' tiket';
        $lines[] = self::footerRoast($total, $topName, $dayOfYear);

        return implode("\n", $lines);
    }

    private static function technicianRoast(int $count, int $seed): string
    {
        if ($count <= 1) {
            return self::pick([
                'baru pemanasan, jangan puas dulu',
                'satu tiket doang, masih bisa senyum',
                'enteng hari ini — manfaatin sebelum banjir',
            ], $seed);
        }

        if ($count <= 3) {
            return self::pick([
                'udah mulai hangat, gas terus',
                'beban normal, jangan ngeluh di grup',
                'lagi naik daun — tiketnya yang dateng',
            ], $seed);
        }

        if ($count <= 5) {
            return self::pick([
                'mode produktif (atau mode survivor)',
                'kopi dobel boleh, kabur jangan',
                'tangan dingin, hati kuat',
            ], $seed);
        }

        return self::pick([
            'bos tiket hari ini — hormati, tapi selesaikan',
            'kalau ini olahraga, kamu lagi marathon',
            'semangat! tiketnya jangan sampai lebih semangat dari kamu',
        ], $seed);
    }

    private static function footerRoast(int $total, string $topName, int $seed): string
    {
        if ($total <= 2) {
            return self::pick([
                '🔥 Santai masih legal. Besok jangan kaget kalau meledak.',
                '✨ Sepi bukan berarti libur — standby tetap on.',
            ], $seed);
        }

        if ($total <= 5) {
            return self::pick([
                '💪 Warm-up bagus. Tutup tiketnya biar namamu bersih.',
                '🚀 Jangan cuma di-assign — gas sampai selesai.',
                '👀 Si '.$topName.' lagi unggul. Kejar atau akui kalah.',
            ], $seed);
        }

        if ($total <= 10) {
            return self::pick([
                '🥵 Padat! Ini bukan curse, ini kepercayaan (katanya).',
                '🛠️ Banyak tiket = banyak peluang pamer skill. Semangat!',
                '🔥 '.$topName.' memimpin papan skor. Sisanya jangan cuma nonton.',
            ], $seed);
        }

        return self::pick([
            '🆘 Mode war. Prioritaskan, kolaborasi, jangan panik sendirian.',
            '⚡ Hujan tiket. Payungnya namanya teamwork — semangat, IT!',
            '🏆 '.$topName.' MVP hari ini (atau korban favorit tiket). Gas semua!',
        ], $seed);
    }

    /**
     * @param  list<string>  $options
     */
    private static function pick(array $options, int $seed): string
    {
        $index = abs($seed) % count($options);

        return $options[$index];
    }
}
