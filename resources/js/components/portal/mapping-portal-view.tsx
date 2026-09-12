import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    CheckSquare,
    Loader2,
    Search,
    Users,
    X,
    XCircle,
} from 'lucide-react';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { DataTablePagination } from '@/components/data-table-pagination';
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

interface MappingPortalViewProps {
    portals: PortalItem[];
    selectedPortal: PortalItem | null;
    users: {
        data: UserItem[];
        links?: { url: string | null; label: string; active: boolean }[];
        current_page?: number;
        last_page?: number;
        total?: number;
    };
    portalCredentials: Record<string | number, UserPortalCredential>;
    departments: string[];
    filters: {
        portal_id: number;
        search: string;
        department: string;
        role: string;
    };
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

export function MappingPortalView({
    portals,
    selectedPortal,
    users,
    portalCredentials,
    departments,
    filters,
}: MappingPortalViewProps) {
    const [localAssignments, setLocalAssignments] = useState<
        Record<
            number,
            {
                has_access: boolean;
                credential_type: CredentialType;
                notes: string;
            }
        >
    >({});

    const getAssignment = useCallback(
        (userId: number) => {
            if (localAssignments[userId]) {
                return localAssignments[userId];
            }
            const cred = portalCredentials[userId];
            return {
                has_access: !!cred && cred.is_active,
                credential_type: (cred?.credential_type ||
                    'use_shared') as CredentialType,
                notes: cred?.notes || '',
            };
        },
        [localAssignments, portalCredentials],
    );

    const [rowStatus, setRowStatus] = useState<
        Record<number, 'idle' | 'saving' | 'saved' | 'error'>
    >({});
    const [isBatchSaving, setIsBatchSaving] = useState(false);

    // Search state & debounced request
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [prevFilterSearch, setPrevFilterSearch] = useState(filters.search);
    if (filters.search !== prevFilterSearch) {
        setPrevFilterSearch(filters.search);
        setSearchTerm(filters.search || '');
    }

    // Optimistic dropdown states for instant UI feedback
    const [selectedPortalId, setSelectedPortalId] = useState(
        selectedPortal?.id ? selectedPortal.id.toString() : '',
    );
    const [prevSelectedPortalProp, setPrevSelectedPortalProp] = useState(
        selectedPortal?.id ? selectedPortal.id.toString() : '',
    );
    const currentPortalPropId = selectedPortal?.id
        ? selectedPortal.id.toString()
        : '';
    if (currentPortalPropId !== prevSelectedPortalProp) {
        setPrevSelectedPortalProp(currentPortalPropId);
        setSelectedPortalId(currentPortalPropId);
    }

    const [selectedDepartment, setSelectedDepartment] = useState(
        filters.department || '_all',
    );
    const [prevDepartmentProp, setPrevDepartmentProp] = useState(
        filters.department || '_all',
    );
    if ((filters.department || '_all') !== prevDepartmentProp) {
        setPrevDepartmentProp(filters.department || '_all');
        setSelectedDepartment(filters.department || '_all');
    }

    const searchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);

    const buildFilteredParams = useCallback(
        (
            overrides: {
                portal_id?: string | number;
                department?: string;
                search?: string;
                role?: string;
                page?: number;
                [key: string]: string | number | undefined;
            } = {},
        ) => {
            const pId =
                overrides.portal_id !== undefined
                    ? overrides.portal_id
                    : selectedPortalId;
            const dept =
                overrides.department !== undefined
                    ? overrides.department
                    : selectedDepartment;
            const qSearch =
                overrides.search !== undefined ? overrides.search : searchTerm;
            const roleVal =
                overrides.role !== undefined
                    ? overrides.role
                    : filters.role || '';

            const params: Record<string, string | number> = {};
            if (
                pId &&
                pId !== '' &&
                pId !== '_none' &&
                pId !== 0 &&
                pId !== '0'
            ) {
                params.portal_id = pId;
            }
            if (dept && dept !== '_all' && dept.trim() !== '') {
                params.department = dept.trim();
            }
            if (
                roleVal &&
                roleVal !== '_all' &&
                String(roleVal).trim() !== ''
            ) {
                params.role = String(roleVal).trim();
            }
            if (qSearch && qSearch.trim() !== '') {
                params.search = qSearch.trim();
            }
            if (overrides.page && Number(overrides.page) > 1) {
                params.page = overrides.page;
            }
            return params;
        },
        [selectedPortalId, selectedDepartment, searchTerm, filters.role],
    );

    const triggerSearchRequest = useCallback(
        (query: string) => {
            const trimmed = query.trim();
            if (trimmed === (filters.search || '').trim()) return;

            router.get(
                '/admin/portals/mapping',
                buildFilteredParams({ search: trimmed }),
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        },
        [filters.search, buildFilteredParams],
    );

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const val = e.target.value;
        setSearchTerm(val);

        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        searchTimeoutRef.current = setTimeout(() => {
            triggerSearchRequest(val);
        }, 400);
    };

    const handleSearchKeyDown = (
        e: React.KeyboardEvent<HTMLInputElement>,
    ) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
            triggerSearchRequest(searchTerm);
        }
    };

    const handleClearSearch = () => {
        setSearchTerm('');
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        if (!(filters.search || '')) return;

        router.get(
            '/admin/portals/mapping',
            buildFilteredParams({ search: '' }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleSelectPortal = (portalId: string) => {
        if (!portalId || portalId === '_none') {
            setSelectedPortalId('');
            router.get(
                '/admin/portals/mapping',
                buildFilteredParams({ portal_id: '' }),
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
            return;
        }
        setSelectedPortalId(portalId);
        router.get(
            '/admin/portals/mapping',
            buildFilteredParams({ portal_id: portalId }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        if (key === 'department') {
            setSelectedDepartment(value);
        }
        router.get(
            '/admin/portals/mapping',
            buildFilteredParams({ [key]: value }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const autoSaveRow = async (
        userId: number,
        hasAccess: boolean,
        credType: CredentialType,
        notes?: string,
        explicitNotesUpdate: boolean = false,
    ) => {
        if (!selectedPortal) return;
        setRowStatus((prev) => ({ ...prev, [userId]: 'saving' }));
        try {
            const csrfToken = getCsrfToken();
            const payload: Record<string, unknown> = {
                portal_id: selectedPortal.id,
                user_id: userId,
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

            setRowStatus((prev) => ({ ...prev, [userId]: 'saved' }));
            setTimeout(() => {
                setRowStatus((prev) => ({ ...prev, [userId]: 'idle' }));
            }, 1500);
        } catch {
            setRowStatus((prev) => ({ ...prev, [userId]: 'error' }));
        }
    };

    const handleToggleUser = (userId: number, checked: boolean) => {
        const current = getAssignment(userId);
        const updated = {
            ...current,
            has_access: checked,
        };

        setLocalAssignments((prev) => ({ ...prev, [userId]: updated }));
        autoSaveRow(
            userId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleCredentialTypeChange = (
        userId: number,
        type: CredentialType,
    ) => {
        const current = getAssignment(userId);
        const updated = {
            ...current,
            credential_type: type,
        };

        setLocalAssignments((prev) => ({ ...prev, [userId]: updated }));
        autoSaveRow(
            userId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleNotesBlur = (userId: number, notes: string) => {
        const current = getAssignment(userId);
        autoSaveRow(
            userId,
            current.has_access,
            current.credential_type,
            notes,
            true,
        );
    };

    const handleNotesChange = (userId: number, notes: string) => {
        const current = getAssignment(userId);
        setLocalAssignments((prev) => ({
            ...prev,
            [userId]: {
                ...current,
                notes,
            },
        }));
    };

    const handleBulkSetAccess = (
        hasAccess: boolean,
        defaultType: CredentialType = 'use_shared',
    ) => {
        if (!selectedPortal) return;
        setIsBatchSaving(true);
        const payload = {
            portal_id: selectedPortal.id,
            assignments: users.data.map((u) => ({
                user_id: u.id,
                has_access: hasAccess,
                credential_type: defaultType,
                notes: getAssignment(u.id).notes || null,
            })),
        };

        router.post('/admin/portals/mapping/sync-portal', payload, {
            preserveScroll: true,
            onFinish: () => setIsBatchSaving(false),
            onSuccess: () => {
                const next: Record<
                    number,
                    {
                        has_access: boolean;
                        credential_type: CredentialType;
                        notes: string;
                    }
                > = {};
                users.data.forEach((u) => {
                    next[u.id] = {
                        notes: getAssignment(u.id).notes,
                        has_access: hasAccess,
                        credential_type: defaultType,
                    };
                });
                setLocalAssignments(next);
            },
        });
    };

    const supportsPersonal =
        selectedPortal?.auth_type === 'personal' ||
        selectedPortal?.auth_type === 'both';
    const supportsShared =
        selectedPortal?.auth_type === 'shared' ||
        selectedPortal?.auth_type === 'both';

    return (
        <div className="space-y-5">
            {/* Portal Selection & Filters */}
            <div className="grid grid-cols-1 gap-4 rounded-xl border border-border bg-card p-4 md:grid-cols-4">
                <div>
                    <label className="text-xs font-semibold text-muted-foreground">
                        Pilih Portal Target
                    </label>
                    <Select
                        value={selectedPortalId}
                        onValueChange={handleSelectPortal}
                    >
                        <SelectTrigger
                            className="mt-1 text-xs"
                            aria-label="Pilih Portal Target"
                        >
                            <SelectValue placeholder="-- Pilih Portal --" />
                        </SelectTrigger>
                        <SelectContent className="max-h-72">
                            <SelectItem value="_none">
                                -- Pilih Portal --
                            </SelectItem>
                            {portals.map((p) => (
                                <SelectItem key={p.id} value={p.id.toString()}>
                                    {p.name} ({p.category})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label className="text-xs font-semibold text-muted-foreground">
                        Filter Departemen
                    </label>
                    <Select
                        value={selectedDepartment}
                        onValueChange={(v) =>
                            handleFilterChange('department', v)
                        }
                        disabled={!selectedPortal}
                    >
                        <SelectTrigger
                            className="mt-1 text-xs"
                            aria-label="Filter Departemen"
                        >
                            <SelectValue placeholder="Semua Departemen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">
                                Semua Departemen
                            </SelectItem>
                            {departments.map((dep) => (
                                <SelectItem key={dep} value={dep}>
                                    {dep}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="md:col-span-2">
                    <label className="text-xs font-semibold text-muted-foreground">
                        Cari Nama / Email / NIK
                    </label>
                    <div className="relative mt-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Ketik nama atau NIK petugas..."
                            aria-label="Cari Nama atau NIK Petugas"
                            value={searchTerm}
                            onChange={handleSearchChange}
                            onKeyDown={handleSearchKeyDown}
                            disabled={!selectedPortal}
                            className="pl-9 pr-8 text-xs"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={handleClearSearch}
                                className="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground hover:text-foreground focus:outline-none"
                                aria-label="Hapus pencarian"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {selectedPortal ? (
                <div className="space-y-4">
                    {/* Bulk Toolbar */}
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/30 p-3">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="mr-1 text-xs font-medium text-muted-foreground">
                        Aksi Cepat Massal:
                    </span>
                    {supportsShared && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isBatchSaving}
                            onClick={() =>
                                handleBulkSetAccess(true, 'use_shared')
                            }
                            className="h-8 gap-1.5 text-xs"
                            aria-label="Izinkan Semua dengan Akun Bersama"
                        >
                            <CheckSquare className="size-3.5 text-primary" />{' '}
                            Izinkan Semua (Akun Bersama)
                        </Button>
                    )}
                    {supportsPersonal && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isBatchSaving}
                            onClick={() =>
                                handleBulkSetAccess(true, 'personal')
                            }
                            className="h-8 gap-1.5 text-xs"
                            aria-label="Izinkan Semua dengan Akun Personal"
                        >
                            <Users className="size-3.5 text-indigo-500" />{' '}
                            Izinkan Semua (Akun Personal)
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={isBatchSaving}
                        onClick={() => handleBulkSetAccess(false)}
                        className="h-8 gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                        aria-label="Cabut Semua Akses Portal"
                    >
                        <XCircle className="size-3.5" /> Cabut Semua
                    </Button>
                </div>

                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CheckCircle2 className="size-3.5 text-emerald-500" />
                    <span>Perubahan baris otomatis tersimpan</span>
                </div>
            </div>

            {/* Matrix Table */}
            <div className="overflow-hidden rounded-xl border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs font-semibold text-muted-foreground uppercase">
                        <tr>
                            <th className="w-16 px-4 py-3 text-center">
                                Akses
                            </th>
                            <th className="px-4 py-3">Nama Petugas & NIK</th>
                            <th className="px-4 py-3">Role & Dept</th>
                            <th className="px-4 py-3">Tipe Kredensial</th>
                            <th className="px-4 py-3">Catatan</th>
                            <th className="w-28 px-4 py-3 text-center">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {users.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-xs text-muted-foreground"
                                >
                                    Tidak ada data petugas yang cocok dengan
                                    filter pencarian.
                                </td>
                            </tr>
                        ) : (
                            users.data.map((user) => {
                                const current = getAssignment(user.id);
                                const status = rowStatus[user.id] || 'idle';

                                return (
                                    <tr
                                        key={user.id}
                                        className={`transition-colors ${
                                            current.has_access
                                                ? 'bg-primary/5 hover:bg-primary/10'
                                                : 'hover:bg-muted/20'
                                        }`}
                                    >
                                        <td className="px-4 py-3 text-center">
                                            <Switch
                                                checked={current.has_access}
                                                onCheckedChange={(checked) =>
                                                    handleToggleUser(
                                                        user.id,
                                                        checked,
                                                    )
                                                }
                                                aria-label={`Akses ${user.name}`}
                                                disabled={status === 'saving'}
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-foreground">
                                                {user.name}
                                            </div>
                                            <div className="font-mono text-xs text-muted-foreground">
                                                {user.simrs_nik
                                                    ? `NIK: ${user.simrs_nik}`
                                                    : user.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <div className="flex items-center gap-1.5">
                                                <Badge
                                                    variant="outline"
                                                    className="text-[10px] capitalize"
                                                >
                                                    {user.role}
                                                </Badge>
                                                <span className="text-muted-foreground">
                                                    {user.dep_id ?? '–'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Select
                                                value={current.credential_type}
                                                onValueChange={(val) =>
                                                    handleCredentialTypeChange(
                                                        user.id,
                                                        val as CredentialType,
                                                    )
                                                }
                                                disabled={!current.has_access || status === 'saving'}
                                            >
                                                <SelectTrigger
                                                    className="h-8 w-[180px] text-xs"
                                                    aria-label={`Tipe kredensial ${user.name}`}
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
                                                        user.id,
                                                        e.target.value,
                                                    )
                                                }
                                                onBlur={(e) =>
                                                    handleNotesBlur(
                                                        user.id,
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Catatan..."
                                                aria-label={`Catatan untuk ${user.name}`}
                                                disabled={!current.has_access || status === 'saving'}
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
                            })
                        )}
                    </tbody>
                </table>
            </div>

            {users.links && users.links.length > 3 && (
                <div className="rounded-xl border border-border overflow-hidden">
                    <DataTablePagination links={users.links} />
                </div>
            )}
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-border p-12 text-center text-sm text-muted-foreground">
                    Pilih salah satu portal pada menu di atas untuk menampilkan
                    daftar hak akses petugas.
                </div>
            )}
        </div>
    );
}
