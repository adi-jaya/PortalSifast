import { Camera, List, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PendingCaptureCommand, PendingRemoteCommand } from './types';

type Props = {
    deviceOnline: boolean;
    canCapture: boolean;
    canRemoteApps: boolean;
    pendingRemote: PendingRemoteCommand | null;
    pendingCapture: PendingCaptureCommand | null;
    onPostCommand: (data: Record<string, string | number>) => void;
    busy: boolean;
};

const STEPS = [
    { key: 'pending', label: 'Antre' },
    { key: 'sent', label: 'Dikirim' },
    { key: 'done', label: 'Selesai' },
] as const;

function CommandStepper({ status }: { status: 'pending' | 'sent' | 'succeeded' | 'failed' }) {
    const activeIndex = status === 'pending' ? 0 : status === 'sent' ? 1 : 2;
    const failed = status === 'failed';

    return (
        <div className="flex items-center gap-1 rounded-lg border border-border/60 bg-muted/30 px-3 py-1.5">
            {STEPS.map((step, idx) => {
                const isActive = idx === activeIndex;
                const isPast = idx < activeIndex || (idx === 2 && status === 'succeeded');

                return (
                    <div key={step.key} className="flex items-center gap-1">
                        {idx > 0 ? <span className="text-muted-foreground/40">→</span> : null}
                        <span
                            className={cn(
                                'rounded px-2 py-0.5 text-xs font-medium',
                                isActive && !failed && 'bg-teal-500/15 text-teal-700 dark:text-teal-300',
                                isActive && failed && 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
                                isPast && 'text-emerald-700 dark:text-emerald-300',
                                !isActive && !isPast && 'text-muted-foreground',
                            )}
                        >
                            {step.label}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}

function pendingLabel(type: PendingRemoteCommand['type']): string {
    switch (type) {
        case 'list_windows':
            return 'Refresh aplikasi';
        case 'list_processes':
            return 'Daftar proses';
        case 'kill_pid':
            return 'Tutup proses';
        case 'capture_desktop':
            return 'Capture layar';
        default:
            return 'Perintah';
    }
}

export function CommandBar({
    deviceOnline,
    canCapture,
    canRemoteApps,
    pendingRemote,
    pendingCapture,
    onPostCommand,
    busy,
}: Props) {
    const activePending = pendingRemote ?? (pendingCapture ? { ...pendingCapture, type: 'capture_desktop' as const } : null);
    const listWaiting = pendingRemote?.type === 'list_windows';
    const processWaiting = pendingRemote?.type === 'list_processes';
    const captureWaiting = pendingCapture !== null;

    if (!canRemoteApps) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-xl border border-border/80 bg-card px-4 py-3 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={busy || !canCapture || captureWaiting}
                    onClick={() => onPostCommand({ type: 'capture_desktop' })}
                    className="border-teal-600/30 hover:bg-teal-500/10"
                >
                    <Camera className={cn('size-3.5', captureWaiting ? 'animate-pulse' : '')} />
                    Capture layar
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={busy || listWaiting}
                    onClick={() => onPostCommand({ type: 'list_windows' })}
                >
                    <RefreshCw className={cn('size-3.5', listWaiting ? 'animate-spin' : '')} />
                    Refresh aplikasi
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={busy || processWaiting}
                    onClick={() => onPostCommand({ type: 'list_processes' })}
                >
                    <List className={cn('size-3.5', processWaiting ? 'animate-pulse' : '')} />
                    Semua proses
                </Button>
            </div>

            {activePending ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs text-muted-foreground">
                        {pendingLabel(activePending.type)} · {activePending.status}
                    </span>
                    <CommandStepper status={activePending.status} />
                </div>
            ) : null}

            {!deviceOnline ? (
                <span className="text-xs text-amber-700 dark:text-amber-300">
                    Agent offline — perintah antre sampai PC online (~10 detik).
                </span>
            ) : null}
        </div>
    );
}
