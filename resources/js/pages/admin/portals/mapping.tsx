import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Globe, ShieldCheck, Users } from 'lucide-react';
import React from 'react';
import { MappingPortalView } from '@/components/portal/mapping-portal-view';
import { MappingUserView } from '@/components/portal/mapping-user-view';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    Portal,
    PortalMappingSummary,
    UserPortalCredential,
} from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    simrs_nik: string | null;
    role: string;
    dep_id: string | null;
}

type PortalItem = Portal | PortalMappingSummary;

interface Props {
    view_mode: 'portal' | 'user';
    portals: PortalItem[];
    selected_portal: PortalItem | null;
    selected_user: UserItem | null;
    users: {
        data: UserItem[];
        links?: { url: string | null; label: string; active: boolean }[];
        current_page?: number;
        last_page?: number;
        total?: number;
    };
    portal_credentials: Record<string | number, UserPortalCredential>;
    user_credentials: Record<string | number, UserPortalCredential>;
    departments: string[];
    all_users?: UserItem[];
    filters: {
        portal_id: number;
        user_id: number;
        search: string;
        department: string;
        role: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
    { title: 'Mapping Akses Petugas', href: '/admin/portals/mapping' },
];

export default function AdminPortalsMapping({
    view_mode,
    portals,
    selected_portal,
    selected_user,
    users,
    portal_credentials,
    user_credentials,
    departments,
    all_users,
    filters,
}: Props) {
    const handleTabChange = (mode: string) => {
        router.get(
            '/admin/portals/mapping',
            { ...filters, view_mode: mode },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mapping Akses Portal Pelaporan" />

            <div className="flex flex-col gap-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-bold tracking-tight text-foreground">
                            <ShieldCheck className="size-6 text-primary" />
                            Mapping Hak Akses Portal Pelaporan
                        </h1>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Atur otorisasi petugas rumah sakit ke portal
                            eksternal dan tentukan penggunaan akun bersama atau
                            akun personal.
                        </p>
                    </div>

                    <Button asChild variant="outline" className="gap-2">
                        <Link href="/admin/portals">
                            <ArrowLeft className="size-4" /> Kembali ke Master
                            Portal
                        </Link>
                    </Button>
                </div>

                <Tabs value={view_mode} onValueChange={handleTabChange}>
                    <TabsList className="h-10">
                        <TabsTrigger value="portal" className="gap-2 text-xs">
                            <Globe className="size-4" /> Matriks Berdasarkan
                            Portal
                        </TabsTrigger>
                        <TabsTrigger value="user" className="gap-2 text-xs">
                            <Users className="size-4" /> Matriks Berdasarkan
                            Petugas
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="portal" className="mt-4">
                        {selected_portal ? (
                            <MappingPortalView
                                key={`portal-${selected_portal.id}-${filters.department || ''}-${filters.search || ''}`}
                                portals={portals}
                                selectedPortal={selected_portal}
                                users={users}
                                portalCredentials={portal_credentials}
                                departments={departments}
                                filters={filters}
                            />
                        ) : (
                            <div className="p-8 text-center text-muted-foreground">
                                Belum ada master portal yang aktif. Silakan
                                tambahkan portal terlebih dahulu.
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="user" className="mt-4">
                        <MappingUserView
                            key={`user-${selected_user?.id ?? 'none'}`}
                            portals={portals}
                            selectedUser={selected_user}
                            users={users}
                            allUsers={all_users || users.data}
                            userCredentials={user_credentials}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
