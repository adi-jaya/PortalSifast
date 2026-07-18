import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { login } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-[100dvh] flex-col md:flex-row">

            {/* ── Left panel — branding ──────────────────────────────────── */}
            <div className="relative flex flex-col justify-between overflow-hidden bg-[#1f2024] px-8 py-10 md:w-[420px] md:min-h-[100dvh] md:px-10 md:py-12 lg:w-[480px]">

                {/* Grid overlay pattern */}
                <div
                    className="pointer-events-none absolute inset-0 opacity-[0.04]"
                    style={{
                        backgroundImage:
                            'linear-gradient(#ffffff 1px, transparent 1px), linear-gradient(90deg, #ffffff 1px, transparent 1px)',
                        backgroundSize: '40px 40px',
                    }}
                />

                {/* Glow blobs */}
                <div
                    className="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full opacity-25"
                    style={{ background: 'radial-gradient(circle, #0A9E8F, transparent 70%)' }}
                />
                <div
                    className="pointer-events-none absolute -bottom-16 right-0 h-48 w-48 rounded-full opacity-15"
                    style={{ background: 'radial-gradient(circle, #0A9E8F, transparent 70%)' }}
                />

                {/* Top — logo */}
                <div className="relative z-10">
                    <Link href={login()} className="inline-flex items-center gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0A9E8F]">
                            <AppLogoIcon className="h-5 w-5 fill-white text-white" />
                        </div>
                        <span className="text-sm font-semibold text-white">Portal Sifast</span>
                    </Link>
                </div>

                {/* Middle — headline */}
                <div className="relative z-10 py-8 md:py-0">
                    <div className="mb-3 inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium uppercase tracking-widest text-white/50">
                        RS Aisyiyah Siti Fatimah
                    </div>
                    <h1 className="text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
                        Sistem Informasi<br />
                        <span className="text-[#0A9E8F]">Administrasi & Layanan</span>
                    </h1>
                    <p className="mt-4 text-sm leading-relaxed text-white/50">
                        Portal terpadu untuk manajemen tiket, kepegawaian, inventaris, dan informasi website rumah sakit.
                    </p>
                </div>

                {/* Bottom — footer text */}
                <div className="relative z-10">
                    <p className="text-xs text-white/25">
                        &copy; {new Date().getFullYear()} RS Aisyiyah Siti Fatimah
                    </p>
                </div>
            </div>

            {/* ── Right panel — form ─────────────────────────────────────── */}
            <div className="flex flex-1 flex-col items-center justify-center bg-[#f5f7fa] px-6 py-10 dark:bg-black md:px-12">
                <div className="w-full max-w-[400px]">

                    {/* Form header */}
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold tracking-tight text-foreground">
                            {title}
                        </h2>
                        {description && (
                            <p className="mt-1.5 text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>

                    {/* Form body */}
                    <div className="rounded-xl border border-border bg-card p-6 shadow-sm">
                        {children}
                    </div>
                </div>
            </div>

        </div>
    );
}
