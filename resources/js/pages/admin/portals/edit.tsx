import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Portal } from '@/types';
import { PortalForm } from './portal-form';

interface Props {
    portal: Portal & { has_shared_password?: boolean };
    categories: string[];
}

export default function AdminPortalsEdit({ portal, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Master Portal Eksternal', href: '/admin/portals' },
        {
            title: `Edit: ${portal.name}`,
            href: `/admin/portals/${portal.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Portal: ${portal.name}`} />

            <div className="flex flex-col gap-6">
                <Heading
                    title={`Edit Portal: ${portal.name}`}
                    description="Perbarui informasi target website, password bersama RS, atau selector form login."
                />

                <PortalForm
                    initialData={portal}
                    categories={categories}
                    isEditing
                />
            </div>
        </AppLayout>
    );
}
