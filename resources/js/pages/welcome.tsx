import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login, register } from '@/routes';
import type { SharedData } from '@/types';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Selamat datang" />
            <div className="relative flex min-h-dvh flex-col items-center overflow-hidden bg-background p-6 text-foreground lg:justify-center lg:p-8">
                <header className="relative z-10 mb-6 w-full max-w-[335px] text-sm lg:max-w-2xl">
                    <nav className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-9 items-center justify-center rounded-xl bg-primary shadow-sm">
                                <AppLogoIcon className="size-5 fill-current text-primary-foreground" />
                            </div>
                            <div className="min-w-0 text-left leading-tight">
                                <p className="truncate text-sm font-semibold">Portal Sifast</p>
                                <p className="truncate text-[11px] text-muted-foreground">
                                    RS Aisyiyah Siti Fatimah
                                </p>
                            </div>
                        </div>

                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="cursor-pointer rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition-colors duration-200 hover:bg-primary-hover"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link
                                    href={login()}
                                    className="cursor-pointer rounded-xl px-4 py-2.5 text-sm font-medium text-foreground transition-colors duration-200 hover:text-primary"
                                >
                                    Masuk
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="cursor-pointer rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition duration-200 hover:bg-primary-hover"
                                    >
                                        Daftar
                                    </Link>
                                )}
                            </div>
                        )}
                    </nav>
                </header>

                <div className="relative z-10 flex w-full flex-1 flex-col items-center justify-center lg:grow">
                    <main className="flex w-full max-w-lg flex-col items-center text-center">
                        <div className="mb-6 flex size-20 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/25">
                            <AppLogoIcon className="size-10 fill-current text-primary-foreground" />
                        </div>
                        <p className="mb-2 text-xs font-bold uppercase tracking-widest text-primary">
                            Portal Sifast
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            RS Aisyiyah Siti Fatimah
                        </h1>
                        <p className="mt-3 max-w-md text-base leading-relaxed text-muted-foreground">
                            Portal administrasi rumah sakit — tiket, kepegawaian, inventaris, dan layanan terpadu.
                        </p>
                        {!auth.user && (
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Link
                                    href={login()}
                                    className="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-border bg-card px-5 py-2.5 text-sm font-medium text-foreground shadow-sm transition duration-200 hover:bg-muted"
                                >
                                    Masuk
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition duration-200 hover:bg-primary-hover"
                                    >
                                        Daftar akun
                                    </Link>
                                )}
                            </div>
                        )}
                    </main>
                </div>
                <div className="hidden h-14 lg:block" />
            </div>
        </>
    );
}
