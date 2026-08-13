import { Monitor, WifiOff } from 'lucide-react';
import { formatRelativeId } from '@/lib/monitoring';
import { cn } from '@/lib/utils';
import type { DesktopSnapshotMeta, LastCaptureCommand, PendingCaptureCommand } from './types';

type Props = {
    snapshotUrl: string | null;
    snapshotAt: string | null;
    snapshotMeta: DesktopSnapshotMeta | null;
    deviceOnline: boolean;
    pendingCapture: PendingCaptureCommand | null;
    lastCaptureCommand: LastCaptureCommand | null;
    canCapture: boolean;
    liveIntervalSec: number;
};

function formatBytes(bytes: number | undefined): string {
    if (!bytes) {
        return '';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function DesktopPreview({
    snapshotUrl,
    snapshotAt,
    snapshotMeta,
    deviceOnline,
    pendingCapture,
    lastCaptureCommand,
    canCapture,
    liveIntervalSec,
}: Props) {
    const waiting = pendingCapture !== null;
    const captureFailed =
        lastCaptureCommand?.status === 'failed' &&
        lastCaptureCommand.error &&
        lastCaptureCommand.finished_at &&
        Date.now() - new Date(lastCaptureCommand.finished_at).getTime() < 120_000;

    const resolution =
        snapshotMeta?.width && snapshotMeta?.height
            ? `${snapshotMeta.width}×${snapshotMeta.height}`
            : null;
    const sizeLabel = formatBytes(snapshotMeta?.size_bytes);

    return (
        <div className="flex min-h-0 flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-950 shadow-lg">
            <div className="flex items-center justify-between border-b border-slate-800 px-4 py-2.5">
                <div className="flex items-center gap-2">
                    <Monitor className="size-4 text-teal-400" />
                    <span className="text-xs font-medium tracking-wide text-slate-300 uppercase">Desktop preview</span>
                </div>
                <div className="flex items-center gap-2">
                    {deviceOnline && canCapture ? (
                        <span className="flex items-center gap-1.5 text-xs text-teal-400">
                            <span className="relative flex size-2">
                                <span className="absolute inline-flex size-full animate-ping rounded-full bg-teal-400 opacity-60" />
                                <span className="relative inline-flex size-2 rounded-full bg-teal-500" />
                            </span>
                            Live {liveIntervalSec}s
                        </span>
                    ) : !deviceOnline ? (
                        <span className="flex items-center gap-1 text-xs text-amber-400">
                            <WifiOff className="size-3" />
                            Offline
                        </span>
                    ) : null}
                    {waiting ? (
                        <span className="rounded border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-xs text-amber-300">
                            Menunggu capture…
                        </span>
                    ) : null}
                </div>
            </div>

            <div className="relative flex flex-1 items-center justify-center bg-slate-950 p-2">
                {snapshotUrl ? (
                    <img
                        src={snapshotUrl}
                        alt="Snapshot desktop"
                        className={cn(
                            'max-h-[480px] w-full rounded border border-slate-800 object-contain',
                            waiting && 'opacity-60',
                        )}
                    />
                ) : (
                    <div className="flex flex-col items-center gap-3 py-16 text-slate-500">
                        <Monitor className="size-10 opacity-40" />
                        <p className="text-sm">
                            {!canCapture
                                ? 'Upgrade agent ≥ 0.5.0 untuk live desktop.'
                                : !deviceOnline
                                  ? 'Agent offline — snapshot akan muncul saat online.'
                                  : waiting
                                    ? 'Menunggu snapshot pertama…'
                                    : 'Belum ada snapshot desktop.'}
                        </p>
                    </div>
                )}

                {!deviceOnline && (
                    <div className="pointer-events-none absolute inset-0 flex items-end justify-center bg-gradient-to-t from-slate-950/80 to-transparent p-4">
                        <span className="rounded border border-slate-700 bg-slate-900/90 px-3 py-1 text-xs text-slate-400">
                            Preview terakhir — perangkat offline
                        </span>
                    </div>
                )}
            </div>

            <div className="border-t border-slate-800 px-4 py-2">
                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                    <span>
                        {snapshotAt ? `Diambil ${formatRelativeId(snapshotAt)}` : '–'}
                        {resolution ? ` · ${resolution}` : ''}
                        {sizeLabel ? ` · ${sizeLabel}` : ''}
                    </span>
                </div>
                {captureFailed ? (
                    <p className="mt-1 text-xs text-rose-400">Gagal capture: {lastCaptureCommand?.error}</p>
                ) : null}
            </div>
        </div>
    );
}
