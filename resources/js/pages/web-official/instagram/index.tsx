import { Head, router, usePage } from '@inertiajs/react';
import { ExternalLink, Instagram, RefreshCw } from 'lucide-react';
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
    { title: 'Instagram', href: '/web-official/instagram' },
];

type InstagramPost = {
    id: string;
    mediaType: string;
    imageUrl: string;
    permalink: string;
    caption: string | null;
    postedAt: string | null;
};

type InstagramStatus = {
    configured: boolean;
    enabled: boolean;
    count: number;
    syncedAt: string | null;
    profileUrl: string;
};

type PageProps = {
    status: InstagramStatus;
    posts: InstagramPost[];
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

export default function WebOfficialInstagramIndex() {
    const { status, posts, apiEndpoint, flash } = usePage<PageProps>().props;

    function syncFeed(): void {
        router.post('/web-official/instagram/sync');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Instagram Feed" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Instagram Feed"
                        description="Kelola sinkronisasi postingan Instagram RS untuk website official."
                    />
                    <Button onClick={syncFeed} disabled={!status.configured}>
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                            <CardDescription>Konfigurasi API</CardDescription>
                            <CardTitle className="text-base">
                                <StatusBadge
                                    tone={status.configured ? 'normal' : 'warning'}
                                    label={status.configured ? 'Sudah lengkap' : 'Belum lengkap'}
                                />
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Postingan di cache</CardDescription>
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
                            <Instagram className="h-4 w-4" />
                            Integrasi API
                        </CardTitle>
                        <CardDescription>
                            Website frontend memanggil endpoint ini (tanpa token Instagram).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <p>
                            <span className="font-medium">Public endpoint:</span>{' '}
                            <code className="rounded bg-muted px-2 py-1">{apiEndpoint}</code>
                        </p>
                        <p>
                            <span className="font-medium">Profil Instagram:</span>{' '}
                            <a
                                href={status.profileUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1 text-primary hover:underline"
                            >
                                {status.profileUrl}
                                <ExternalLink className="h-3.5 w-3.5" />
                            </a>
                        </p>
                        {!status.configured ? (
                            <p className="rounded-md border border-dashed px-3 py-2 text-muted-foreground">
                                Set <code>INSTAGRAM_FEED_ENABLED=true</code>,{' '}
                                <code>INSTAGRAM_ACCESS_TOKEN</code>, dan <code>INSTAGRAM_USER_ID</code> di file{' '}
                                <code>.env</code>, lalu jalankan sinkronisasi. Panduan lengkap ada di{' '}
                                <code>docs/webofficial/instagram-setup-meta.md</code>.
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                <div>
                    <h2 className="mb-3 text-lg font-semibold">Pratinjau postingan</h2>
                    {posts.length === 0 ? (
                        <div className="rounded-xl border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada postingan di cache. Klik &quot;Sinkronkan sekarang&quot; setelah konfigurasi API
                            selesai.
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {posts.map((post) => (
                                <a
                                    key={post.id}
                                    href={post.permalink}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="group overflow-hidden rounded-xl border bg-card shadow-sm transition hover:shadow-md"
                                >
                                    <img
                                        src={post.imageUrl}
                                        alt={post.caption ?? 'Postingan Instagram'}
                                        className="aspect-square w-full object-cover transition group-hover:scale-[1.02]"
                                        loading="lazy"
                                    />
                                    <div className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">{formatDate(post.postedAt)}</p>
                                        {post.caption ? (
                                            <p className="line-clamp-2 text-sm">{post.caption}</p>
                                        ) : (
                                            <p className="text-sm text-muted-foreground">Tanpa caption</p>
                                        )}
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
