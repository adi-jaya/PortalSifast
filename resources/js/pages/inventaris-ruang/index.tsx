import { Head, Link, router } from '@inertiajs/react';
import { Search, Plus, Pencil, Trash2, Package } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Master Ruang', href: '/inventaris-ruang' },
];

type Item = {
    id_ruang: string;
    nama_ruang: string;
};

type Paginated = {
    data: Item[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string };
};

export default function Index({ items, filters }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/inventaris-ruang', { q: search || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Ruang" />
            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading title="Master Ruang" description="Master data inventaris SIMRS" />
                    <Button asChild>
                        <Link href="/inventaris-ruang/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Ruang
                        </Link>
                    </Button>
                </div>
                <form onSubmit={handleSearch} className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit">Cari</Button>
                </form>
                <div className="rounded-2xl border bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">ID Ruang</th>
                                    <th className="px-4 py-3 font-medium">Nama Ruang</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="p-0">
                                            <EmptyState
                                                title="Belum ada data"
                                                description="Tambahkan data master baru."
                                                icon={<Package className="size-7" />}
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((item) => (
                                        <tr key={item.id_ruang} className="border-b last:border-0 hover:bg-muted/50">
                                            <td className="px-4 py-3 font-mono">{item.id_ruang}</td>
                                            <td className="px-4 py-3">{item.nama_ruang}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/inventaris-ruang/${item.id_ruang}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => {
                                                            if (confirm('Hapus data ini?')) {
                                                                router.delete(`/inventaris-ruang/${item.id_ruang}`);
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {items.last_page > 1 && (
                        <div className="flex flex-wrap justify-center gap-2 border-t px-4 py-3">
                            {items.links.map((link, i) => (
                                <span key={i}>
                                    {link.url ? (
                                        <Button size="sm" variant={link.active ? 'default' : 'outline'} asChild>
                                            <Link href={link.url} preserveState>
                                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <span
                                            className="inline-flex size-8 items-center justify-center text-muted-foreground"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
                <p className="text-sm text-muted-foreground">Total: {items.total}</p>
            </div>
        </AppLayout>
    );
}
