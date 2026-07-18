import { getCsrfToken } from '@/lib/utils';

export type AsetMasterTipe = 'kategori' | 'jenis' | 'merk' | 'produsen' | 'distributor';

export type AsetMasterItem = {
    id: number;
    kode: string;
    nama: string;
};

type QuickCreateResponse = {
    item: AsetMasterItem;
    created: boolean;
};

export async function quickCreateAsetMaster(
    tipe: AsetMasterTipe,
    nama: string,
): Promise<QuickCreateResponse | null> {
    const trimmed = nama.trim();
    if (trimmed.length < 2) {
        return null;
    }

    const response = await fetch(`/aset/master/${tipe}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken() ?? '',
        },
        body: JSON.stringify({ nama: trimmed }),
    });

    if (!response.ok) {
        return null;
    }

    return (await response.json()) as QuickCreateResponse;
}
