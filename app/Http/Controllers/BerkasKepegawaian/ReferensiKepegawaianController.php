<?php

namespace App\Http\Controllers\BerkasKepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Departemen;
use App\Models\JnjJabatan;
use App\Models\KelompokJabatan;
use App\Models\Pendidikan;
use App\Models\SttsKerja;
use App\Models\SttsWp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferensiKepegawaianController extends Controller
{
    /**
     * @var array<string, array{label: string, columns: list<string>}>
     */
    public const TYPES = [
        'bidang' => [
            'label' => 'Bidang',
            'columns' => ['nama'],
        ],
        'departemen' => [
            'label' => 'Departemen',
            'columns' => ['dep_id', 'nama'],
        ],
        'stts_kerja' => [
            'label' => 'Status Kerja',
            'columns' => ['stts', 'ktg', 'indek', 'hakcuti'],
        ],
        'stts_wp' => [
            'label' => 'Status WP',
            'columns' => ['stts', 'ktg'],
        ],
        'pendidikan' => [
            'label' => 'Pendidikan',
            'columns' => ['tingkat', 'indek', 'gapok1', 'kenaikan', 'maksimal'],
        ],
        'jnj_jabatan' => [
            'label' => 'Jenjang Jabatan',
            'columns' => ['kode', 'nama', 'tnj', 'indek'],
        ],
        'kelompok_jabatan' => [
            'label' => 'Kelompok Jabatan',
            'columns' => ['kode_kelompok', 'nama_kelompok', 'indek'],
        ],
    ];

    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', 'departemen');
        if (! array_key_exists($type, self::TYPES)) {
            $type = 'departemen';
        }

        $q = trim((string) $request->query('q', ''));
        $meta = self::TYPES[$type];
        $query = $this->baseQuery($type);

        if ($q !== '') {
            $query->where(function (Builder $inner) use ($q, $meta): void {
                foreach ($meta['columns'] as $column) {
                    $inner->orWhere($column, 'like', "%{$q}%");
                }
            });
        }

        $items = $query
            ->paginate(30)
            ->withQueryString()
            ->through(fn ($row) => array_combine(
                $meta['columns'],
                array_map(fn (string $column) => $row->{$column}, $meta['columns']),
            ));

        return Inertia::render('berkas-kepegawaian/referensi', [
            'type' => $type,
            'typeLabel' => $meta['label'],
            'columns' => $meta['columns'],
            'types' => collect(self::TYPES)
                ->map(fn (array $item, string $key) => [
                    'value' => $key,
                    'label' => $item['label'],
                ])
                ->values()
                ->all(),
            'items' => $items,
            'filters' => [
                'q' => $q,
                'type' => $type,
            ],
        ]);
    }

    private function baseQuery(string $type): Builder
    {
        return match ($type) {
            'bidang' => Bidang::query()->orderBy('nama'),
            'departemen' => Departemen::query()->orderBy('nama'),
            'stts_kerja' => SttsKerja::query()->orderBy('stts'),
            'stts_wp' => SttsWp::query()->orderBy('stts'),
            'pendidikan' => Pendidikan::query()->orderBy('tingkat'),
            'jnj_jabatan' => JnjJabatan::query()->orderBy('kode'),
            'kelompok_jabatan' => KelompokJabatan::query()->orderBy('kode_kelompok'),
            default => Departemen::query()->orderBy('nama'),
        };
    }
}
