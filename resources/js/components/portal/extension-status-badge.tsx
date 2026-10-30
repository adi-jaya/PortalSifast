import { CheckCircle2, Download, HelpCircle, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { ExtensionStatus } from '@/types/portal';

interface ExtensionStatusBadgeProps {
    status: ExtensionStatus;
    onOpenGuide?: () => void;
    className?: string;
}

export function ExtensionStatusBadge({
    status,
    onOpenGuide,
    className,
}: ExtensionStatusBadgeProps) {
    if (status.isChecking) {
        return (
            <Badge
                variant="outline"
                className={cn(
                    'flex items-center gap-1.5 border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400',
                    className,
                )}
            >
                <Loader2 className="h-3 w-3 animate-spin text-slate-400" />
                <span className="text-xs font-medium">
                    Memeriksa Ekstensi...
                </span>
            </Badge>
        );
    }

    if (status.isInstalled) {
        return (
            <Badge
                variant="outline"
                className={cn(
                    'flex items-center gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400',
                    className,
                )}
                title="Ekstensi Sifast Autofill aktif dan siap digunakan."
            >
                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                <span className="text-xs font-semibold">
                    Ekstensi Aktif (v{status.version || '1.0.0'})
                </span>
            </Badge>
        );
    }

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={onOpenGuide}
            className={cn(
                'h-7 gap-1.5 rounded-full border-amber-300 bg-amber-50 px-2.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50',
                className,
            )}
        >
            <Download className="h-3 w-3 text-amber-600 dark:text-amber-400" />
            <span>Ekstensi Belum Terpasang</span>
            <HelpCircle className="h-3 w-3 opacity-60" />
        </Button>
    );
}
