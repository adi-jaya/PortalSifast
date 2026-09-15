import { Chrome, HelpCircle, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ExtensionGuideBannerProps {
    isInstalled: boolean;
    isChecking: boolean;
    onOpenGuide: () => void;
    className?: string;
}

export function ExtensionGuideBanner({
    isInstalled,
    isChecking,
    onOpenGuide,
    className,
}: ExtensionGuideBannerProps) {
    const [dismissed, setDismissed] = useState(false);

    // Jangan tampilkan jika sudah terpasang, sedang checking, atau ditutup sementara
    if (isInstalled || isChecking || dismissed) {
        return null;
    }

    return (
        <div
            className={cn(
                'relative flex flex-col gap-3 rounded-xl border border-amber-200/80 bg-gradient-to-r from-amber-50/90 via-amber-50/50 to-orange-50/60 p-4 text-amber-900 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:from-amber-950/30 dark:via-amber-950/20 dark:to-orange-950/20 dark:text-amber-200',
                className,
            )}
        >
            <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                    <Chrome className="h-5 w-5" />
                </div>
                <div>
                    <div className="flex items-center gap-2">
                        <h4 className="text-sm font-semibold">
                            Ekstensi Browser SIFAST Belum Terpasang
                        </h4>
                    </div>
                    <p className="mt-0.5 text-xs text-amber-800/90 dark:text-amber-300/80">
                        Untuk menikmati pengisian username & password secara
                        otomatis ke portal eksternal (Kemenkes & BKKBN), silakan
                        pasang ekstensi browser resmi SIMRS.
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-2 self-end sm:self-center">
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={onOpenGuide}
                    className="h-8 gap-1.5 border-amber-300 bg-white/80 text-xs font-semibold text-amber-900 hover:bg-amber-100 hover:text-amber-950 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200 dark:hover:bg-amber-900"
                >
                    <HelpCircle className="h-3.5 w-3.5" />
                    <span>Panduan Pemasangan</span>
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    onClick={() => setDismissed(true)}
                    className="h-8 w-8 text-amber-600 hover:bg-amber-100/60 hover:text-amber-900 dark:text-amber-400 dark:hover:bg-amber-900/40"
                    title="Tutup pemberitahuan"
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
