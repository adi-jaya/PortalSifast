import { getCsrfToken } from '@/lib/utils';

export type AsetMasterTipe = 'kategori' | 'jenis' | 'merk' | 'produsen' | 'distributor';

export type AsetMasterItem = {
    id: number;
    kode: string;
    nama: string;
    aset_merk_id?: number | null;
};

type QuickCreateResponse = {
    item: AsetMasterItem;
    created: boolean;
};

export async function quickCreateAsetMaster(
    tipe: AsetMasterTipe,
    nama: string,
    extra: { aset_merk_id?: number | null } = {},
): Promise<QuickCreateResponse | null> {
    const trimmed = nama.trim();
    if (trimmed.length < 2) {
        return null;
    }

    const body: Record<string, unknown> = { nama: trimmed };
    if (tipe === 'jenis' && extra.aset_merk_id) {
        body.aset_merk_id = extra.aset_merk_id;
    }

    const response = await fetch(`/aset/master/${tipe}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken() ?? '',
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        return null;
    }

    return (await response.json()) as QuickCreateResponse;
}
