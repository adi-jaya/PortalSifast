import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Search, UserCircle } from 'lucide-react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type RecentEmployee = {
    nik: string;
    name: string | null;
    unit: string | null;
};

type Props = {
    recentEmployees: RecentEmployee[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payroll', href: '/payroll' },
    { title: 'Riwayat Pegawai', href: '/payroll/employee-history' },
];

export default function EmployeeHistorySearch({ recentEmployees }: Props) {
    const [nik, setNik] = useState('');
    const [error, setError] = useState('');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const trimmed = nik.trim();
        if (!trimmed) {
            setError('NIK wajib diisi.');
            return;
        }

        setError('');
        router.get(`/payroll/employee/${encodeURIComponent(trimmed)}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Riwayat Pegawai" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/payroll" aria-label="Kembali ke payroll">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-semibold">Riwayat Pegawai</h1>
                        <p className="text-sm text-muted-foreground">
                            Cari riwayat gaji berdasarkan NIK pegawai.
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4 rounded-lg border p-4">
                    <div className="space-y-2">
                        <Label htmlFor="nik">NIK pegawai</Label>
                        <div className="flex gap-2">
                            <Input
                                id="nik"
                                value={nik}
                                onChange={(e) => setNik(e.target.value)}
                                placeholder="Contoh: 01.01.01.2000"
                                autoComplete="off"
                            />
                            <Button type="submit">
                                <Search className="mr-2 h-4 w-4" />
                                Cari
                            </Button>
                        </div>
                        <InputError message={error} />
                    </div>
                </form>

                {recentEmployees.length > 0 && (
                    <section className="space-y-3 rounded-lg border p-4">
                        <h2 className="text-sm font-medium text-muted-foreground">Pegawai terbaru</h2>
                        <ul className="divide-y">
                            {recentEmployees.map((employee) => (
                                <li key={employee.nik}>
                                    <Link
                                        href={`/payroll/employee/${encodeURIComponent(employee.nik)}`}
                                        className="flex items-center gap-3 py-3 transition-colors hover:bg-muted/50"
                                    >
                                        <UserCircle className="h-5 w-5 shrink-0 text-muted-foreground" />
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {employee.name ?? employee.nik}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {employee.nik}
                                                {employee.unit ? ` · ${employee.unit}` : ''}
                                            </p>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
