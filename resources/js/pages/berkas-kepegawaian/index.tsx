import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    pegawai: {
        data: unknown[];
        links: unknown[];
        total: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
];

export default function BerkasKepegawaianIndex({ pegawai }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berkas Kepegawaian" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Berkas Kepegawaian</h1>
                    <p className="text-sm text-muted-foreground">
                        {pegawai.total} pegawai terdaftar
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
