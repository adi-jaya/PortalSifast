import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Archive, Star } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Option = { value: string; label: string };

type Feedback = {
    id: string;
    full_name: string;
    phone: string;
    service_unit: string;
    rating: number;
    message: string;
    status: string;
    source: string;
    ip_address: string | null;
    user_agent: string | null;
    admin_notes: string | null;
    created_at: string | null;
    updated_at: string | null;
    resolved_at: string | null;
};

type Props = {
    feedback: Feedback;
    statusOptions: Option[];
};

function formatDate(value: string | null): string {
    if (!value) {
        return '–';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

export default function WebOfficialKritikSaranShow({ feedback, statusOptions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Website Official', href: '/web-official' },
        { title: 'Kritik & Saran', href: '/web-official/kritik-saran' },
        { title: feedback.full_name, href: '#' },
    ];

    const { data, setData, put, processing, errors } = useForm({
        status: feedback.status,
        admin_notes: feedback.admin_notes ?? '',
    });

    function submit(e: React.FormEvent): void {
        e.preventDefault();
        put(`/web-official/kritik-saran/${feedback.id}`);
    }

    function archive(): void {
        if (confirm('Arsipkan pengaduan ini?')) {
            router.delete(`/web-official/kritik-saran/${feedback.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kritik & Saran: ${feedback.full_name}`} />

            <div className="flex flex-col gap-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/web-official/kritik-saran">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    <StatusBadge status={feedback.status} />
                </div>

                <Heading title={feedback.full_name} description={`Diterima: ${formatDate(feedback.created_at)}`} />

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border bg-card p-6">
                        <h2 className="text-sm font-semibold">Detail pengaduan</h2>
                        <dl className="space-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Telepon</dt>
                                <dd className="font-medium">{feedback.phone}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Unit pelayanan</dt>
                                <dd>{feedback.service_unit}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Rating</dt>
                                <dd className="inline-flex items-center gap-1">
                                    <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
                                    {feedback.rating} / 5
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Sumber</dt>
                                <dd>{feedback.source}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Pesan</dt>
                                <dd className="mt-1 whitespace-pre-wrap rounded-lg bg-muted/40 p-3">{feedback.message}</dd>
                            </div>
                            {feedback.resolved_at ? (
                                <div>
                                    <dt className="text-muted-foreground">Diselesaikan</dt>
                                    <dd>{formatDate(feedback.resolved_at)}</dd>
                                </div>
                            ) : null}
                        </dl>
                    </div>

                    <form onSubmit={submit} className="space-y-4 rounded-xl border bg-card p-6">
                        <h2 className="text-sm font-semibold">Tindak lanjut internal</h2>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {statusOptions.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="admin_notes">Catatan internal</Label>
                            <Textarea
                                id="admin_notes"
                                value={data.admin_notes}
                                onChange={(e) => setData('admin_notes', e.target.value)}
                                rows={5}
                                placeholder="Catatan tindak lanjut untuk tim mutu…"
                            />
                            <InputError message={errors.admin_notes} />
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan…' : 'Simpan perubahan'}
                            </Button>
                            {feedback.status !== 'archived' && (
                                <Button type="button" variant="outline" onClick={archive}>
                                    <Archive className="mr-2 h-4 w-4" />
                                    Arsipkan
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                <details className="rounded-xl border bg-card p-4 text-sm text-muted-foreground">
                    <summary className="cursor-pointer font-medium text-foreground">Metadata teknis</summary>
                    <div className="mt-3 space-y-2">
                        <p>IP: {feedback.ip_address ?? '–'}</p>
                        <p className="break-all">User-Agent: {feedback.user_agent ?? '–'}</p>
                        <p>Diperbarui: {formatDate(feedback.updated_at)}</p>
                    </div>
                </details>
            </div>
        </AppLayout>
    );
}
