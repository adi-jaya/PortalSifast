import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { PortalForm } from './portal-form';

interface Props {
    categories: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
    { title: 'Tambah Portal', href: '/admin/portals/create' },
];

export default function AdminPortalsCreate({ categories }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Master Portal Eksternal" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Tambah Master Portal Eksternal"
                    description="Daftarkan platform website pelaporan baru, akun bersama RS, dan konfigurasi DOM selector login."
                />

                <PortalForm categories={categories} />
            </div>
        </AppLayout>
    );
}
