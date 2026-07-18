import { Head, router, usePage } from '@inertiajs/react';
import { ExternalLink, Newspaper, RefreshCw } from 'lucide-react';
import Heading from '@/components/heading';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Berita Eksternal', href: '/web-official/berita-eksternal' },
];

type RssSourceStatus = {
    id: string;
    name: string;
    url: string;
    websiteUrl: string;
    itemCount: number;
    lastError: string | null;
};

type RssItem = {
    id: string;
    sourceId: string;
    sourceName: string;
    sourceWebsiteUrl: string;
    title: string;
    link: string;
    excerpt: string | null;
    imageUrl: string | null;
    publishedAt: string | null;
    categories: string[];
};

type PageProps = {
    status: {
        configured: boolean;
        enabled: boolean;
        count: number;
        syncedAt: string | null;
        sources: RssSourceStatus[];
    };
    items: RssItem[];
    apiEndpoint: string;
    flash: {
        success?: string;
        error?: string;
    };
};

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          });
}

export default function WebOfficialExternalRssIndex() {
    const { status, items, apiEndpoint, flash } = usePage<PageProps>().props;

    function syncFeeds(): void {
        router.post('/web-official/berita-eksternal/sync');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berita Eksternal RSS" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Berita Eksternal (RSS)"
                        description="Agregasi berita dari Suara Muhammadiyah dan Muhammadiyah.or.id untuk website official."
                    />
                    <Button onClick={syncFeeds} disabled={!status.configured}>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Sinkronkan sekarang
                    </Button>
                </div>

                {flash.success ? (
                    <div className="rounded-lg border border-normal/20 bg-normal-bg px-4 py-3 text-sm text-normal">
                        {flash.success}
                    </div>
                ) : null}

                {flash.error ? (
                    <div className="rounded-lg border border-urgent/20 bg-urgent-bg px-4 py-3 text-sm text-urgent">
                        {flash.error}
                    </div>
                ) : null}

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Status modul</CardDescription>
                            <CardTitle className="text-base">
                                <StatusBadge
                                    tone={status.enabled ? 'normal' : 'neutral'}
                                    label={status.enabled ? 'Aktif' : 'Nonaktif'}
                                />
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total berita di cache</CardDescription>
                            <CardTitle className="text-base">{status.count}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Terakhir sinkron</CardDescription>
                            <CardTitle className="text-base">{formatDate(status.syncedAt)}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Newspaper className="h-4 w-4" />
                            Sumber RSS
                        </CardTitle>
                        <CardDescription>
                            Public API: <code className="rounded bg-muted px-2 py-1">{apiEndpoint}</code>
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {status.sources.map((source) => (
                            <div key={source.id} className="rounded-lg border px-4 py-3 text-sm">
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="font-medium">{source.name}</p>
                                        <p className="text-muted-foreground">{source.url}</p>
                                    </div>
                                    <div className="text-right">
                                        <p>{source.itemCount} item</p>
                                        {source.lastError ? (
                                            <StatusBadge tone="urgent" label={source.lastError} />
                                        ) : (
                                            <StatusBadge tone="normal" label="OK" />
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                        <p className="text-xs text-muted-foreground">
                            Catatan: URL <code>suaramuhammadiyah.id/feed/</code> sudah tidak aktif. Default
                            backend memakai <code>web.suaramuhammadiyah.id/feed/</code>.
                        </p>
                    </CardContent>
                </Card>

                <div>
                    <h2 className="mb-3 text-lg font-semibold">Pratinjau berita</h2>
                    {items.length === 0 ? (
                        <div className="rounded-xl border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada berita di cache. Klik &quot;Sinkronkan sekarang&quot;.
                        </div>
                    ) : (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {items.map((item) => (
                                <a
                                    key={item.id}
                                    href={item.link}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="flex gap-4 rounded-xl border bg-card p-4 shadow-sm transition hover:shadow-md"
                                >
                                    {item.imageUrl ? (
                                        <img
                                            src={item.imageUrl}
                                            alt={item.title}
                                            className="h-24 w-24 shrink-0 rounded-lg object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex h-24 w-24 shrink-0 items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground">
                                            RSS
                                        </div>
                                    )}
                                    <div className="min-w-0 space-y-1">
                                        <p className="text-xs text-muted-foreground">
                                            {item.sourceName} · {formatDate(item.publishedAt)}
                                        </p>
                                        <p className="line-clamp-2 font-medium">{item.title}</p>
                                        {item.excerpt ? (
                                            <p className="line-clamp-2 text-sm text-muted-foreground">{item.excerpt}</p>
                                        ) : null}
                                        <span className="inline-flex items-center gap-1 text-xs text-primary">
                                            Baca di sumber
                                            <ExternalLink className="h-3 w-3" />
                                        </span>
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
