export type PendingRemoteCommand = {
    id: number;
    type: 'list_windows' | 'list_processes' | 'kill_pid' | 'capture_desktop';
    status: 'pending' | 'sent' | 'succeeded' | 'failed';
    created_at: string | null;
};

export type PendingCaptureCommand = {
    id: number;
    status: 'pending' | 'sent';
    created_at: string | null;
};

export type LastListCommand = {
    id: number;
    type: 'list_windows' | 'list_processes';
    status: 'succeeded' | 'failed';
    error?: string | null;
    finished_at?: string | null;
};

export type LastKillCommand = {
    id: number;
    status: 'succeeded' | 'failed';
    pid: number;
    exe: string;
    error?: string | null;
    finished_at?: string | null;
};

export type LastCaptureCommand = {
    id: number;
    status: 'succeeded' | 'failed';
    error?: string | null;
    finished_at?: string | null;
};

export type DesktopSnapshotMeta = {
    format?: string;
    width?: number;
    height?: number;
    size_bytes?: number;
};

export type WindowApp = {
    pid: number;
    exe: string;
    title: string;
};

export type ProcessRow = {
    pid: number;
    exe: string;
    path?: string | null;
    user?: string | null;
    cpu_percent?: number | null;
};

export type SuspiciousProcess = {
    pid: number;
    exe: string;
    path?: string | null;
    cpu_percent?: number | null;
    reasons: string[];
};
