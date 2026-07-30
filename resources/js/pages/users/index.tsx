import { Head, Link, router } from '@inertiajs/react';
import { Pencil, X } from 'lucide-react';
import { useCallback, useState } from 'react';

import { DataTablePagination } from '@/components/data-table-pagination';
import { DataTableSearch } from '@/components/data-table-search';
import { DataTableToolbar } from '@/components/data-table-toolbar';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { RowActionButton } from '@/components/row-action-button';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Daftar User', href: '/users' },
];

type UserItem = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    simrs_nik: string | null;
    source: string | null;
    role: string;
    dep_id: string | null;
    created_at: string;
    can_manage_web_official: boolean;
    has_web_official_access: boolean;
};

type PaginatedUsers = {
    data: UserItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type UserFilters = {
    search: string;
    role: string;
    dep_id: string;
};

type Props = {
    users: PaginatedUsers;
    filters: UserFilters;
};

export default function UsersIndex({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilters = useCallback(
        (newFilters: Partial<UserFilters>) => {
            router.get(
                '/users',
                { ...filters, ...newFilters },
                { preserveState: true, replace: true },
            );
        },
        [filters],
    );

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const clearFilters = () => {
        setSearch('');
        router.get('/users', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = !!(filters.search || filters.role || filters.dep_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar User" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Daftar User"
                        description="Semua user yang dapat mengakses aplikasi"
                    />
                    <Button asChild className="btn-text-md">
                        <Link href="/users/create">Tambah User</Link>
                    </Button>
                </div>

                <DataTableToolbar>
                    <form onSubmit={handleSearch} className="flex min-w-0 flex-1 gap-2">
                        <DataTableSearch
                            placeholder="Cari nama, email, atau NIK..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button type="submit" variant="secondary" className="btn-text-md h-11">
                            Cari
                        </Button>
                    </form>
                    <Select
                        value={filters.role || '_all'}
                        onValueChange={(v) => applyFilters({ role: v === '_all' ? '' : v })}
                    >
                        <SelectTrigger className="h-11 w-[140px] rounded-xl">
                            <SelectValue placeholder="Semua Role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Role</SelectItem>
                            <SelectItem value="admin">Admin</SelectItem>
                            <SelectItem value="staff">Staff</SelectItem>
                            <SelectItem value="pemohon">Pemohon</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.dep_id || '_all'}
                        onValueChange={(v) => applyFilters({ dep_id: v === '_all' ? '' : v })}
                    >
                        <SelectTrigger className="h-11 w-[140px] rounded-xl">
                            <SelectValue placeholder="Semua Dept" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Dept</SelectItem>
                            <SelectItem value="IT">IT</SelectItem>
                            <SelectItem value="IPS">IPS</SelectItem>
                        </SelectContent>
                    </Select>
                    {hasActiveFilters && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-11 rounded-[10px]"
                            onClick={clearFilters}
                            aria-label="Reset filter"
                        >
                            <X className="size-4" />
                        </Button>
                    )}
                </DataTableToolbar>

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th className="col-secondary">NIK</th>
                                    <th>Email</th>
                                    <th className="col-secondary">No. HP</th>
                                    <th>Role</th>
                                    <th className="col-secondary">Website Official</th>
                                    <th className="col-secondary">Dep</th>
                                    <th className="col-secondary">Sumber</th>
                                    <th className="cell-action">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={9} className="cell-empty">
                                            <EmptyState
                                                title="Belum ada user"
                                                description="Tambahkan user baru untuk mengakses aplikasi."
                                                action={
                                                    <Button asChild>
                                                        <Link href="/users/create">Tambah User</Link>
                                                    </Button>
                                                }
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    users.data.map((user) => (
                                        <tr key={user.id}>
                                            <td className="!font-medium">{user.name}</td>
                                            <td className="col-secondary">{user.simrs_nik ?? '–'}</td>
                                            <td>{user.email}</td>
                                            <td className="col-secondary">{user.phone ?? '–'}</td>
                                            <td className="cell-badge">
                                                <Badge
                                                    variant={
                                                        user.role === 'admin'
                                                            ? 'warning'
                                                            : user.role === 'staff'
                                                              ? 'info'
                                                              : 'neutral'
                                                    }
                                                >
                                                    {user.role}
                                                </Badge>
                                            </td>
                                            <td className="col-secondary cell-badge">
                                                {user.has_web_official_access ? (
                                                    <StatusBadge
                                                        tone={
                                                            user.role === 'admin' ? 'primary' : 'normal'
                                                        }
                                                        label={
                                                            user.role === 'admin' ? 'Admin' : 'Staff'
                                                        }
                                                    />
                                                ) : (
                                                    <span className="text-muted-foreground">–</span>
                                                )}
                                            </td>
                                            <td className="col-secondary">{user.dep_id ?? '–'}</td>
                                            <td className="col-secondary cell-badge">
                                                <Badge
                                                    variant={
                                                        user.source === 'simrs' ? 'follow-up' : 'neutral'
                                                    }
                                                >
                                                    {user.source ?? 'manual'}
                                                </Badge>
                                            </td>
                                            <td className="cell-action">
                                                <RowActionButton asChild aria-label={`Edit ${user.name}`}>
                                                    <Link href={`/users/${user.id}/edit`}>
                                                        <Pencil />
                                                    </Link>
                                                </RowActionButton>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {users.last_page > 1 && <DataTablePagination links={users.links} />}
                </div>

                <p className="text-sm text-muted-foreground">
                    Total: {users.total} user{users.total !== 1 ? 's' : ''}
                </p>
            </div>
        </AppLayout>
    );
}
