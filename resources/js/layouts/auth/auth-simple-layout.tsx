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
            <div className="relative flex flex-col justify-between overflow-hidden bg-primary px-8 py-10 md:w-[420px] md:min-h-[100dvh] md:px-10 md:py-12 lg:w-[480px]">
                <div
                    className="pointer-events-none absolute inset-0 opacity-[0.08]"
                    style={{
                        backgroundImage:
                            'linear-gradient(#ffffff 1px, transparent 1px), linear-gradient(90deg, #ffffff 1px, transparent 1px)',
                        backgroundSize: '40px 40px',
                    }}
                />

                <div className="pointer-events-none absolute -left-16 -top-16 h-72 w-72 rounded-full bg-white/20 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-20 right-0 h-56 w-56 rounded-full bg-black/10 blur-3xl" />

                <div className="relative z-10">
                    <Link
                        href={login()}
                        className="inline-flex items-center gap-3 rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                            <AppLogoIcon className="h-5 w-5 fill-primary text-primary" />
                        </div>
                        <span className="text-sm font-semibold text-white">Portal Sifast</span>
                    </Link>
                </div>

                <div className="relative z-10 py-8 md:py-0">
                    <div className="mb-3 inline-block rounded-full border border-white/25 bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-white/90">
                        RS Aisyiyah Siti Fatimah
                    </div>
                    <h1 className="text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
                        Sistem Informasi
                        <br />
                        <span className="text-white/90">Administrasi & Layanan</span>
                    </h1>
                    <p className="mt-4 max-w-sm text-sm leading-relaxed text-white/80">
                        Portal terpadu untuk manajemen tiket, kepegawaian, inventaris, dan informasi website rumah sakit.
                    </p>
                </div>

                <div className="relative z-10">
                    <p className="text-xs text-white/55">
                        &copy; {new Date().getFullYear()} RS Aisyiyah Siti Fatimah
                    </p>
                </div>
            </div>

            <div className="flex flex-1 flex-col items-center justify-center bg-background px-6 py-10 md:px-12">
                <div className="w-full max-w-[400px]">
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

                    <div className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
