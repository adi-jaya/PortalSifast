import {
    ExternalLink,
    Globe,
    KeyRound,
    Loader2,
    User,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { PortalCardItem } from '@/types/portal';

interface PortalCardProps {
    portal: PortalCardItem;
    isExtensionInstalled: boolean;
    onOpenPersonalModal: (portal: PortalCardItem) => void;
}

export function PortalCard({
    portal,
    isExtensionInstalled,
    onOpenPersonalModal,
}: PortalCardProps) {
    const [isLaunching, setIsLaunching] = useState(false);
    const [imageError, setImageError] = useState(false);

    const handleLaunch = async () => {
        // Fallback: Jika ekstensi belum terpasang, langsung buka tab baru ke URL target
        if (!isExtensionInstalled) {
            window.open(portal.url, '_blank', 'noopener,noreferrer');
            return;
        }

        setIsLaunching(true);
        try {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
            const csrfToken = tokenMeta ? tokenMeta.content : '';

            const response = await fetch(`/portal-pelaporan/${portal.id}/dispatch-token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Gagal memproses kredensial portal.');
            }

            const payload = await response.json();

            // Pancarkan event ke ekstensi browser SIFAST
            window.dispatchEvent(
                new CustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: payload,
                }),
            );
        } catch (err: unknown) {
            // Bila terjadi galat dispatch, fallback tetap membuka portal di tab baru
            console.warn('[SIFAST Portal] Dispatch gagal, fallback ke direct tab:', err);
            window.open(portal.url, '_blank', 'noopener,noreferrer');
        } finally {
            setTimeout(() => {
                setIsLaunching(false);
            }, 600);
        }
    };

    // Inisial untuk fallback logo jika icon_url tidak ada
    const initials = portal.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();

    const isShared = portal.credential_type === 'use_shared';
    const isPersonal = portal.credential_type === 'personal';

    return (
        <Card className="group relative flex flex-col justify-between overflow-hidden border-slate-200/80 bg-white transition-all duration-200 hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-800">
            <CardHeader className="p-4 pb-3">
                <div className="flex items-start justify-between gap-3">
                    {/* Logo Portal atau Fallback Inisial */}
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-2xs dark:border-slate-800 dark:bg-slate-800/80">
                        {portal.icon_url && !imageError ? (
                            <img
                                src={portal.icon_url}
                                alt={`Logo ${portal.name}`}
                                className="h-full w-full object-contain p-1.5"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-emerald-500/10 to-teal-500/20 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                {initials || <Globe className="h-5 w-5 text-emerald-600" />}
                            </div>
                        )}
                    </div>

                    {/* Badge Kategori */}
                    <Badge
                        variant="secondary"
                        className="max-w-[130px] truncate text-[11px] font-medium text-slate-600 dark:text-slate-300"
                        title={portal.category}
                    >
                        {portal.category}
                    </Badge>
                </div>

                <div className="mt-3">
                    <h3 className="line-clamp-1 text-base font-bold text-slate-900 group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-400">
                        {portal.name}
                    </h3>
                    <p className="mt-1 line-clamp-2 min-h-[32px] text-xs text-slate-500 dark:text-slate-400">
                        {portal.description || 'Tidak ada deskripsi portal.'}
                    </p>
                </div>
            </CardHeader>

            <CardContent className="p-4 pt-0">
                {/* Indikator Akun / Kredensial */}
                <div className="mt-1 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5 dark:border-slate-800/80">
                    <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        Tipe Akun:
                    </span>

                    {isShared && (
                        <Badge
                            variant="outline"
                            className="gap-1 border-teal-200 bg-teal-50/70 text-[11px] font-medium text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-300"
                        >
                            <Users className="h-3 w-3 text-teal-600 dark:text-teal-400" />
                            <span>Akun Bersama RS</span>
                        </Badge>
                    )}

                    {isPersonal && (
                        <Badge
                            variant="outline"
                            className={cn(
                                'gap-1 text-[11px] font-medium',
                                portal.has_personal_credential
                                    ? 'border-indigo-200 bg-indigo-50/70 text-indigo-800 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-300'
                                    : 'border-amber-200 bg-amber-50/70 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300',
                            )}
                            title={
                                portal.personal_username
                                    ? `Akun: ${portal.personal_username}`
                                    : 'Kredensial personal belum diatur'
                            }
                        >
                            <User className="h-3 w-3 text-indigo-600 dark:text-indigo-400" />
                            <span>
                                {portal.has_personal_credential
                                    ? portal.personal_username || 'Akun Pribadi'
                                    : 'Pribadi (Belum Diatur)'}
                            </span>
                        </Badge>
                    )}

                    {!isShared && !isPersonal && (
                        <Badge
                            variant="outline"
                            className="border-slate-200 bg-slate-50 text-[11px] text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                        >
                            <span>Belum Dikonfigurasi</span>
                        </Badge>
                    )}
                </div>
            </CardContent>

            <CardFooter className="flex items-center gap-2 border-t border-slate-100 bg-slate-50/50 p-3 dark:border-slate-800/80 dark:bg-slate-900/40">
                {/* Tombol Atur Akun Pribadi (Hanya jika diizinkan) */}
                {portal.can_configure_personal && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onOpenPersonalModal(portal)}
                        className="h-8 w-8 p-0 text-slate-600 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400"
                        title="Atur Username & Password Akun Pribadi"
                    >
                        <KeyRound className="h-4 w-4" />
                    </Button>
                )}

                {/* Tombol Buka Portal Utama */}
                <Button
                    type="button"
                    size="sm"
                    onClick={handleLaunch}
                    disabled={isLaunching}
                    className="h-8 flex-1 gap-1.5 bg-emerald-600 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                >
                    {isLaunching ? (
                        <>
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            <span>Membuka...</span>
                        </>
                    ) : (
                        <>
                            <span>Buka Portal</span>
                            <ExternalLink className="h-3.5 w-3.5" />
                        </>
                    )}
                </Button>
            </CardFooter>
        </Card>
    );
}
