import { Head, Link } from '@inertiajs/react';
import {
    BedDouble,
    ChevronRight,
    FileText,
    Handshake,
    Instagram,
    MessageSquareWarning,
    Newspaper,
    Stethoscope,
    TicketPercent,
} from 'lucide-react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
];

type ModuleCard = {
    icon: React.ElementType;
    title: string;
    description: string;
    href: string;
    label: string;
    color: string;
};

const modules: ModuleCard[] = [
    {
        icon: FileText,
        title: 'Berita & Informasi',
        description: 'Tulis berita, promo, dan artikel kesehatan yang tampil di halaman informasi website.',
        href: '/web-official/articles',
        label: 'Kelola artikel',
        color: 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
    },
    {
        icon: BedDouble,
        title: 'Kamar Inap',
        description: 'Atur data kamar, fasilitas, harga, dan foto untuk halaman kamar inap website.',
        href: '/web-official/rooms',
        label: 'Kelola kamar',
        color: 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400',
    },
    {
        icon: TicketPercent,
        title: 'Promosi',
        description: 'Kelola promo dan paket spesial yang tampil di halaman promosi website.',
        href: '/web-official/promosi',
        label: 'Kelola promosi',
        color: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
    },
    {
        icon: Stethoscope,
        title: 'Poliklinik',
        description: 'Kurasi konten landing page poliklinik spesialis untuk website resmi.',
        href: '/web-official/poliklinik',
        label: 'Kelola poliklinik',
        color: 'bg-violet-500/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400',
    },
    {
        icon: Handshake,
        title: 'Rekanan Kami',
        description: 'Kelola logo dan data mitra/rekanan yang tampil di halaman rekanan website.',
        href: '/web-official/rekanan',
        label: 'Kelola rekanan',
        color: 'bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400',
    },
    {
        icon: MessageSquareWarning,
        title: 'Kritik & Saran',
        description: 'Kelola pengaduan dari pengunjung website official (formulir Tidak Puas di /kritik-saran).',
        href: '/web-official/kritik-saran',
        label: 'Lihat pengaduan',
        color: 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400',
    },
    {
        icon: Instagram,
        title: 'Instagram Feed',
        description: 'Sinkronkan postingan Instagram RS agar otomatis tampil di website official.',
        href: '/web-official/instagram',
        label: 'Kelola Instagram',
        color: 'bg-pink-500/10 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400',
    },
    {
        icon: Newspaper,
        title: 'Berita Eksternal RSS',
        description: 'Tarik berita dari Suara Muhammadiyah dan Muhammadiyah.or.id untuk ditampilkan di website.',
        href: '/web-official/berita-eksternal',
        label: 'Kelola RSS',
        color: 'bg-cyan-500/10 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400',
    },
];

export default function WebOfficialDashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Website Official" />

            <div className="flex flex-col gap-6">
                <div data-aos="fade-down" data-aos-duration="350">
                    <Heading
                        title="Website Official RS"
                        description="Kelola konten berita, promo, artikel kesehatan, dan kamar inap untuk website resmi rumah sakit."
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {modules.map((mod, i) => (
                        <Link
                            key={mod.href}
                            href={mod.href}
                            className="module-card group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            data-aos="fade-up"
                            data-aos-delay={i * 60}
                        >
                            <div className="mb-4 flex items-start justify-between">
                                <div className={cn('flex h-10 w-10 items-center justify-center rounded-lg', mod.color)}>
                                    <mod.icon className="h-5 w-5" />
                                </div>
                                <ChevronRight className="h-4 w-4 text-muted-foreground/40 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:text-primary" />
                            </div>
                            <h2 className="text-[15px] font-semibold text-foreground">{mod.title}</h2>
                            <p className="mt-1 flex-1 text-sm leading-relaxed text-muted-foreground">
                                {mod.description}
                            </p>
                            <span className="mt-4 inline-flex items-center gap-1 text-xs font-medium text-primary transition-gap duration-200">
                                {mod.label}
                                <ChevronRight className="h-3 w-3" />
                            </span>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
