import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    CheckSquare,
    Globe,
    Loader2,
    UserCheck,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    CredentialType,
    Portal,
    PortalMappingSummary,
    UserPortalCredential,
} from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    simrs_nik: string | null;
    role: string;
    dep_id: string | null;
}

type PortalItem = Portal | PortalMappingSummary;

interface MappingUserViewProps {
    portals: PortalItem[];
    selectedUser: UserItem | null;
    users: {
        data: UserItem[];
    };
    allUsers?: UserItem[];
    userCredentials: Record<string | number, UserPortalCredential>;
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function MappingUserView({
    portals,
    selectedUser,
    users,
    allUsers,
    userCredentials,
}: MappingUserViewProps) {
    const availableUsers = allUsers && allUsers.length > 0 ? allUsers : users.data;
    const [userSearchTerm, setUserSearchTerm] = useState('');

    const filteredUsers = React.useMemo(() => {
        if (!userSearchTerm.trim()) return availableUsers;
        const q = userSearchTerm.toLowerCase();
        return availableUsers.filter(
            (u) =>
                u.name.toLowerCase().includes(q) ||
                (u.simrs_nik && u.simrs_nik.toLowerCase().includes(q)) ||
                u.email.toLowerCase().includes(q) ||
                (u.dep_id && u.dep_id.toLowerCase().includes(q)),
        );
    }, [availableUsers, userSearchTerm]);
    const [assignments, setAssignments] = useState<
        Record<
            number,
            {
                has_access: boolean;
                credential_type: CredentialType;
                notes: string;
            }
        >
    >(() => {
        const initial: Record<
            number,
            {
                has_access: boolean;
                credential_type: CredentialType;
                notes: string;
            }
        > = {};
        portals.forEach((p) => {
            const cred = userCredentials[p.id];
            initial[p.id] = {
                has_access: !!cred && cred.is_active,
                credential_type: (cred?.credential_type ||
                    'use_shared') as CredentialType,
                notes: cred?.notes || '',
            };
        });
        return initial;
    });

    const [rowStatus, setRowStatus] = useState<
        Record<number, 'idle' | 'saving' | 'saved' | 'error'>
    >({});
    const [isBatchSaving, setIsBatchSaving] = useState(false);

    const handleSelectUser = (userId: string) => {
        router.get(
            '/admin/portals/mapping',
            { user_id: userId, view_mode: 'user' },
            { preserveState: true },
        );
    };

    const autoSaveRow = async (
        portalId: number,
        hasAccess: boolean,
        credType: CredentialType,
        notes?: string,
        explicitNotesUpdate: boolean = false,
    ) => {
        if (!selectedUser) return;
        setRowStatus((prev) => ({ ...prev, [portalId]: 'saving' }));
        try {
            const csrfToken = getCsrfToken();
            const payload: Record<string, unknown> = {
                portal_id: portalId,
                user_id: selectedUser.id,
                has_access: hasAccess,
                credential_type: credType,
            };

            if (explicitNotesUpdate) {
                payload.notes = notes && notes.trim() !== '' ? notes.trim() : null;
            }

            const response = await fetch('/admin/portals/mapping/save-row', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) throw new Error('Gagal menyimpan perubahan');

            setRowStatus((prev) => ({ ...prev, [portalId]: 'saved' }));
            setTimeout(() => {
                setRowStatus((prev) => ({ ...prev, [portalId]: 'idle' }));
            }, 1500);
        } catch {
            setRowStatus((prev) => ({ ...prev, [portalId]: 'error' }));
        }
    };

    const handleTogglePortal = (portalId: number, checked: boolean) => {
        const current = assignments[portalId] || {
            has_access: false,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            has_access: checked,
        };

        setAssignments((prev) => ({ ...prev, [portalId]: updated }));
        autoSaveRow(
            portalId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleCredentialTypeChange = (
        portalId: number,
        type: CredentialType,
    ) => {
        const current = assignments[portalId] || {
            has_access: true,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            credential_type: type,
        };

        setAssignments((prev) => ({ ...prev, [portalId]: updated }));
        autoSaveRow(
            portalId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleNotesBlur = (portalId: number, notes: string) => {
        const current = assignments[portalId];
        if (!current) return;
        autoSaveRow(
            portalId,
            current.has_access,
            current.credential_type,
            notes,
            true,
        );
    };

    const handleNotesChange = (portalId: number, notes: string) => {
        setAssignments((prev) => ({
            ...prev,
            [portalId]: {
                ...(prev[portalId] || {
                    has_access: false,
                    credential_type: 'use_shared',
                    notes: '',
                }),
                notes,
            },
        }));
    };

    const handleBulkSetAccess = (hasAccess: boolean) => {
        if (!selectedUser) return;
        setIsBatchSaving(true);

        const payload = {
            user_id: selectedUser.id,
            assignments: portals.map((p) => ({
                portal_id: p.id,
                has_access: hasAccess,
                credential_type:
                    assignments[p.id]?.credential_type || 'use_shared',
                notes: assignments[p.id]?.notes || null,
            })),
        };

        router.post('/admin/portals/mapping/sync-user', payload, {
            preserveScroll: true,
            onFinish: () => setIsBatchSaving(false),
            onSuccess: () => {
                setAssignments((prev) => {
                    const next = { ...prev };
                    portals.forEach((p) => {
                        next[p.id] = {
                            ...(next[p.id] || {
                                notes: '',
                                credential_type: 'use_shared',
                            }),
                            has_access: hasAccess,
                        };
                    });
                    return next;
                });
            },
        });
    };

    return (
        <div className="space-y-5">
            {/* User Selector Header */}
            <div className="flex flex-col justify-between gap-4 rounded-xl border border-border bg-card p-4 md:flex-row md:items-center">
                <div className="w-full md:w-96">
                    <label className="text-xs font-semibold text-muted-foreground">
                        Pilih Petugas Rumah Sakit
                    </label>
                    <div className="mt-1 space-y-1.5">
                        <Input
                            placeholder="Cari nama / NIK / unit..."
                            value={userSearchTerm}
                            onChange={(e) => setUserSearchTerm(e.target.value)}
                            className="h-8 text-xs"
                            aria-label="Filter Petugas"
                        />
                        <Select
                            value={selectedUser?.id?.toString() ?? ''}
                            onValueChange={handleSelectUser}
                        >
                            <SelectTrigger
                                className="h-9"
                                aria-label="Pilih Petugas Rumah Sakit"
                            >
                                <SelectValue placeholder="-- Pilih Petugas --" />
                            </SelectTrigger>
                            <SelectContent className="max-h-72">
                                {filteredUsers.length === 0 ? (
                                    <div className="p-2 text-center text-xs text-muted-foreground">
                                        Tidak ada petugas ditemukan
                                    </div>
                                ) : (
                                    filteredUsers.map((u) => (
                                        <SelectItem key={u.id} value={u.id.toString()}>
                                            {u.name} (
                                            {u.simrs_nik
                                                ? `NIK: ${u.simrs_nik}`
                                                : u.email}
                                            {u.dep_id ? ` • ${u.dep_id}` : ''}
                                            )
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {selectedUser && (
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <UserCheck className="size-5" />
                        </div>
                        <div>
                            <div className="text-sm font-semibold text-foreground">
                                {selectedUser.name}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Role:{' '}
                                <span className="capitalize">
                                    {selectedUser.role}
                                </span>{' '}
                                | Dept: {selectedUser.dep_id ?? '–'}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {selectedUser ? (
                <div className="space-y-4">
                    {/* Quick Action Toolbar */}
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/30 p-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="mr-1 text-xs font-medium text-muted-foreground">
                                Aksi Cepat:
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isBatchSaving}
                                onClick={() => handleBulkSetAccess(true)}
                                className="h-8 gap-1.5 text-xs"
                                aria-label="Izinkan Semua Portal"
                            >
                                <CheckSquare className="size-3.5 text-primary" />{' '}
                                Izinkan Semua Portal
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isBatchSaving}
                                onClick={() => handleBulkSetAccess(false)}
                                className="h-8 gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                aria-label="Cabut Semua Portal"
                            >
                                <XCircle className="size-3.5" /> Cabut Semua
                                Portal
                            </Button>
                        </div>

                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <CheckCircle2 className="size-3.5 text-emerald-500" />
                            <span>Perubahan baris otomatis tersimpan</span>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-border bg-muted/50 text-xs font-semibold text-muted-foreground uppercase">
                                <tr>
                                    <th className="w-16 px-4 py-3 text-center">
                                        Akses
                                    </th>
                                    <th className="px-4 py-3">Portal Target</th>
                                    <th className="px-4 py-3">Kategori</th>
                                    <th className="px-4 py-3">
                                        Tipe Kredensial
                                    </th>
                                    <th className="px-4 py-3">Catatan Akses</th>
                                    <th className="w-28 px-4 py-3 text-center">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {portals.map((portal) => {
                                    const current = assignments[portal.id] || {
                                        has_access: false,
                                        credential_type: 'use_shared',
                                        notes: '',
                                    };

                                    const supportsPersonal =
                                        portal.auth_type === 'personal' ||
                                        portal.auth_type === 'both';
                                    const supportsShared =
                                        portal.auth_type === 'shared' ||
                                        portal.auth_type === 'both';

                                    const status =
                                        rowStatus[portal.id] || 'idle';

                                    return (
                                        <tr
                                            key={portal.id}
                                            className={`transition-colors ${
                                                current.has_access
                                                    ? 'bg-primary/5 hover:bg-primary/10'
                                                    : 'hover:bg-muted/20'
                                            }`}
                                        >
                                            <td className="px-4 py-3 text-center">
                                                <Switch
                                                    checked={current.has_access}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        handleTogglePortal(
                                                            portal.id,
                                                            checked,
                                                        )
                                                    }
                                                    aria-label={`Akses portal ${portal.name}`}
                                                    disabled={status === 'saving'}
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2 font-medium text-foreground">
                                                    <Globe className="size-4 text-primary" />
                                                    <span>{portal.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {portal.category}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Select
                                                    value={
                                                        current.credential_type
                                                    }
                                                    onValueChange={(val) =>
                                                        handleCredentialTypeChange(
                                                            portal.id,
                                                            val as CredentialType,
                                                        )
                                                    }
                                                    disabled={
                                                        !current.has_access || status === 'saving'
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className="h-8 w-[180px] text-xs"
                                                        aria-label={`Tipe kredensial ${portal.name}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            value="use_shared"
                                                            disabled={
                                                                !supportsShared
                                                            }
                                                        >
                                                            Akun Bersama RS
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="personal"
                                                            disabled={
                                                                !supportsPersonal
                                                            }
                                                        >
                                                            Akun Pribadi Petugas
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    value={current.notes}
                                                    onChange={(e) =>
                                                        handleNotesChange(
                                                            portal.id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    onBlur={(e) =>
                                                        handleNotesBlur(
                                                            portal.id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Catatan..."
                                                    aria-label={`Catatan akses ${portal.name}`}
                                                    disabled={
                                                        !current.has_access || status === 'saving'
                                                    }
                                                    className="h-8 text-xs"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {status === 'saving' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                                        <Loader2 className="size-3 animate-spin" />{' '}
                                                        Menyimpan...
                                                    </span>
                                                )}
                                                {status === 'saved' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                                        <Check className="size-3" />{' '}
                                                        Tersimpan
                                                    </span>
                                                )}
                                                {status === 'error' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-destructive">
                                                        <AlertCircle className="size-3" />{' '}
                                                        Gagal
                                                    </span>
                                                )}
                                                {status === 'idle' && (
                                                    <span className="text-[11px] text-muted-foreground/60">
                                                        {current.has_access
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-border p-12 text-center text-sm text-muted-foreground">
                    Pilih salah satu petugas pada menu di atas untuk menampilkan
                    daftar izin portal.
                </div>
            )}
        </div>
    );
}
