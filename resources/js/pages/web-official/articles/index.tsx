import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    { title: 'Website Official', href: '/web-official' },
    { title: 'Berita & Informasi', href: '/web-official/articles' },
];

type ArticleRow = {
    id: string;
    slug: string;
    title: string;
    category: string;
    status: string;
    published_at: string | null;
    updated_at: string | null;
};

type Paginated = {
    data: ArticleRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Option = { value: string; label: string };

type Props = {
    articles: Paginated;
    filters: { q: string; status: string; category: string };
    categoryOptions: Option[];
    statusOptions: Option[];
};

export default function WebOfficialArticlesIndex({ articles, filters, categoryOptions, statusOptions }: Props) {
    function applyFilters(next: Partial<typeof filters>): void {
        router.get(
            '/web-official/articles',
            { ...filters, ...next },
            { preserveState: true, replace: true },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berita & Informasi" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading title="Berita & Informasi" description="Kelola artikel untuk website official." />
                    <Button asChild>
                        <Link href="/web-official/articles/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah artikel
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Cari judul…"
                        value={filters.q}
                        onChange={(e) => applyFilters({ q: e.target.value })}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.status || '__all__'}
                        onValueChange={(v) => applyFilters({ status: v === '__all__' ? '' : v })}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua status</SelectItem>
                            {statusOptions.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.category || '__all__'}
                        onValueChange={(v) => applyFilters({ category: v === '__all__' ? '' : v })}
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua kategori</SelectItem>
                            {categoryOptions.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-2xl border border-border/80 bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Judul</th>
                                    <th className="px-4 py-3 font-medium">Kategori</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Diperbarui</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {articles.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada artikel.
                                        </td>
                                    </tr>
                                ) : (
                                    articles.data.map((article) => (
                                        <tr key={article.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{article.title}</div>
                                                <div className="text-xs text-muted-foreground">{article.slug}</div>
                                            </td>
                                            <td className="px-4 py-3">{article.category}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={article.status} />
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {article.updated_at
                                                    ? new Date(article.updated_at).toLocaleDateString('id-ID')
                                                    : '–'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/web-official/articles/${article.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {article.status !== 'archived' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (confirm('Arsipkan artikel ini?')) {
                                                                    router.delete(`/web-official/articles/${article.id}`);
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {articles.last_page > 1 && (
                        <div className="flex flex-wrap items-center justify-center gap-2 border-t px-4 py-3">
                            {articles.links.map((link, i) => (
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

                <p className="text-sm text-muted-foreground">Total: {articles.total} artikel</p>
            </div>
        </AppLayout>
    );
}
