import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Eye,
    EyeOff,
    Globe,
    Lock,
    Shield,
    Sparkles,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';
import { FormConfigEditor } from '@/components/portal/form-config-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { FormConfig, Portal, PortalAuthType } from '@/types';

interface PortalFormProps {
    initialData?: Partial<Portal>;
    categories: string[];
    isEditing?: boolean;
}

const DEFAULT_CATEGORIES = [
    'Kemenkes',
    'BKKBN',
    'Kemendukbangga',
    'Mutu & Akreditasi',
];

export function PortalForm({
    initialData,
    categories,
    isEditing = false,
}: PortalFormProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [isInstantUploading, setIsInstantUploading] = useState(false);
    const [isInstantRemoving, setIsInstantRemoving] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const categoryOptions = React.useMemo(() => {
        const seen = new Set<string>();
        const uniqueList: string[] = [];

        [
            ...(initialData?.category ? [initialData.category] : []),
            ...categories,
            ...DEFAULT_CATEGORIES,
        ].forEach((cat) => {
            const trimmed = (cat || '').trim();
            if (!trimmed) return;
            const normalized = trimmed.toLowerCase();
            if (!seen.has(normalized)) {
                seen.add(normalized);
                uniqueList.push(trimmed);
            }
        });

        return uniqueList.sort((a, b) =>
            a.localeCompare(b, undefined, { sensitivity: 'base' }),
        );
    }, [categories, initialData?.category]);

    const defaultFormConfig: FormConfig = {
        is_spa: false,
        wait_timeout_ms: 10000,
        username_field: {
            selectors: [
                "input[name='username']",
                "input[name='email']",
                '#username',
                '#email',
            ],
        },
        password_field: {
            selectors: ["input[name='password']", '#password'],
        },
        extra_fields: [],
        auto_submit: false,
    };

    const { data, setData, post, processing, errors } = useForm<{
        _method?: string;
        name: string;
        slug: string;
        category: string;
        url: string;
        url_pattern: string;
        icon_file: File | null;
        remove_logo: boolean;
        description: string;
        auth_type: PortalAuthType;
        shared_username: string;
        shared_password: string;
        form_config: FormConfig;
        is_active: boolean;
        sort_order: number;
    }>({
        ...(isEditing ? { _method: 'put' } : {}),
        name: initialData?.name ?? '',
        slug: initialData?.slug ?? '',
        category: initialData?.category ?? (categories[0] || 'Kemenkes'),
        url: initialData?.url ?? '',
        url_pattern: initialData?.url_pattern ?? '',
        icon_file: null,
        remove_logo: false,
        description: initialData?.description ?? '',
        auth_type: (initialData?.auth_type ?? 'both') as PortalAuthType,
        shared_username: initialData?.shared_username ?? '',
        shared_password: '',
        form_config: (initialData?.form_config ??
            defaultFormConfig) as FormConfig,
        is_active: initialData?.is_active ?? true,
        sort_order: initialData?.sort_order ?? 0,
    });

    useEffect(() => {
        if (data.icon_file) {
            const objectUrl = URL.createObjectURL(data.icon_file);
            setPreviewUrl(objectUrl);
            return () => URL.revokeObjectURL(objectUrl);
        } else {
            setPreviewUrl(null);
        }
    }, [data.icon_file]);

    const handleAutoSlug = (force = false) => {
        if (!data.name) return;
        if (!force && (isEditing || data.slug)) return;
        const slugified = data.name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-');
        setData('slug', slugified);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData((prev) => ({
            ...prev,
            icon_file: file,
            remove_logo: false,
        }));
    };

    const handleClearStagedFile = () => {
        setData('icon_file', null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleMarkRemoveLogo = () => {
        setData((prev) => ({
            ...prev,
            icon_file: null,
            remove_logo: true,
        }));
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleUndoRemoveLogo = () => {
        setData('remove_logo', false);
    };

    const handleQuickUpload = () => {
        if (!data.icon_file || !initialData?.id) return;
        setIsInstantUploading(true);
        router.post(
            `/admin/portals/${initialData.id}/logo`,
            { icon_file: data.icon_file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    handleClearStagedFile();
                },
                onFinish: () => {
                    setIsInstantUploading(false);
                },
            },
        );
    };

    const handleInstantRemove = () => {
        if (!initialData?.id) return;
        if (!confirm('Yakin ingin menghapus berkas logo ini secara langsung?')) {
            return;
        }
        setIsInstantRemoving(true);
        router.delete(`/admin/portals/${initialData.id}/logo`, {
            preserveScroll: true,
            onSuccess: () => {
                handleClearStagedFile();
                setData('remove_logo', false);
            },
            onFinish: () => {
                setIsInstantRemoving(false);
            },
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing && initialData?.id) {
            post(`/admin/portals/${initialData.id}`, {
                forceFormData: true,
            });
        } else {
            post('/admin/portals', {
                forceFormData: true,
            });
        }
    };

    const currentDisplayUrl =
        previewUrl || (!data.remove_logo ? initialData?.icon_url : null);
    const hasExistingLogo = Boolean(initialData?.icon_url && !data.remove_logo);
    const showSharedSection =
        data.auth_type === 'shared' || data.auth_type === 'both';

    return (
        <form onSubmit={handleSubmit} className="max-w-5xl space-y-6">
            {/* Section 1: Target Website Information */}
            <div className="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-2.5 border-b border-border pb-2">
                    <Globe className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">
                            Informasi Target Website
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Detail platform eksternal dan alamat login resmi
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="name">
                            Nama Resmi Portal{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            onBlur={() => handleAutoSlug(false)}
                            placeholder="Contoh: SIRS Online Kemkes"
                            className="mt-1"
                            required
                        />
                        {errors.name && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <Label htmlFor="slug">
                                Slug URL{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <button
                                type="button"
                                onClick={() => handleAutoSlug(true)}
                                className="flex items-center gap-1 text-xs text-primary hover:underline"
                            >
                                <Sparkles className="size-3" /> Auto Slug
                            </button>
                        </div>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            placeholder="sirs-online-kemkes"
                            className="mt-1 font-mono text-xs"
                            required
                        />
                        {errors.slug && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.slug}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="category">
                            Kategori Portal{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="category"
                            list="category-suggestions"
                            value={data.category}
                            onChange={(e) =>
                                setData('category', e.target.value)
                            }
                            placeholder="Pilih atau ketik kategori..."
                            className="mt-1"
                            required
                        />
                        <datalist id="category-suggestions">
                            {categoryOptions.map((cat) => (
                                <option key={cat} value={cat} />
                            ))}
                        </datalist>
                        {errors.category && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.category}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="url">
                            URL Form Login Target{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="url"
                            type="url"
                            value={data.url}
                            onChange={(e) => setData('url', e.target.value)}
                            placeholder="https://akun-yankes.kemkes.go.id"
                            className="mt-1 font-mono text-xs"
                            required
                        />
                        {errors.url && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.url}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="url_pattern">
                            URL Match Pattern (Wildcard)
                        </Label>
                        <Input
                            id="url_pattern"
                            value={data.url_pattern}
                            onChange={(e) =>
                                setData('url_pattern', e.target.value)
                            }
                            placeholder="*://*.kemkes.go.id/*"
                            className="mt-1 font-mono text-xs"
                        />
                        <p className="mt-1 text-[11px] text-muted-foreground">
                            Pola tab browser target untuk pencocokan injeksi
                            autofill
                        </p>
                    </div>

                    {/* Logo Uploader Box */}
                    <div className="md:col-span-2 space-y-2 rounded-xl border border-border/80 bg-muted/20 p-4">
                        <Label className="text-sm font-semibold text-foreground">
                            Berkas Logo Portal
                        </Label>
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            {/* 80x80 Preview Box */}
                            <div className="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-background shadow-xs">
                                {currentDisplayUrl ? (
                                    <img
                                        src={currentDisplayUrl}
                                        alt="Preview Logo"
                                        className="size-full object-contain p-1.5"
                                    />
                                ) : (
                                    <Globe className="size-8 text-muted-foreground/60" />
                                )}
                            </div>

                            <div className="flex flex-1 flex-col gap-2">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    onChange={handleFileChange}
                                    className="hidden"
                                    id="portal-logo-input"
                                />

                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            fileInputRef.current?.click()
                                        }
                                        className="gap-1.5 text-xs"
                                    >
                                        <Upload className="size-3.5" />
                                        {hasExistingLogo || data.icon_file
                                            ? 'Ganti Logo'
                                            : 'Pilih Logo'}
                                    </Button>

                                    {data.icon_file && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={handleClearStagedFile}
                                            className="gap-1 text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="size-3.5" /> Batal
                                        </Button>
                                    )}

                                    {isEditing &&
                                        data.icon_file &&
                                        initialData?.id && (
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                size="sm"
                                                disabled={isInstantUploading}
                                                onClick={handleQuickUpload}
                                                className="gap-1.5 text-xs font-medium"
                                            >
                                                <Upload className="size-3.5" />
                                                {isInstantUploading
                                                    ? 'Mengunggah...'
                                                    : 'Unggah Cepat'}
                                            </Button>
                                        )}

                                    {hasExistingLogo && !data.icon_file && (
                                        <>
                                            {isEditing && initialData?.id ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={isInstantRemoving}
                                                    onClick={handleInstantRemove}
                                                    className="gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="size-3.5" />
                                                    {isInstantRemoving
                                                        ? 'Menghapus...'
                                                        : 'Hapus Logo Instan'}
                                                </Button>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={handleMarkRemoveLogo}
                                                    className="gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="size-3.5" /> Hapus Logo
                                                </Button>
                                            )}
                                        </>
                                    )}

                                    {data.remove_logo && (
                                        <span className="flex items-center gap-2 text-xs text-destructive">
                                            <span>Logo akan dihapus saat disimpan</span>
                                            <button
                                                type="button"
                                                onClick={handleUndoRemoveLogo}
                                                className="underline hover:text-foreground"
                                            >
                                                Batalkan
                                            </button>
                                        </span>
                                    )}
                                </div>

                                <p className="text-[11px] text-muted-foreground">
                                    Format: PNG, JPG, WEBP, atau SVG (Maks. 2 MB). Disarankan rasio 1:1.
                                </p>

                                {errors.icon_file && (
                                    <p className="text-xs font-medium text-destructive">
                                        {errors.icon_file}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <Label htmlFor="description">
                        Deskripsi Singkat Portal
                    </Label>
                    <Textarea
                        id="description"
                        rows={2}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Deskripsi singkat fungsi pelaporan data pada sistem eksternal ini..."
                        className="mt-1 text-xs"
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="sort_order">
                            Urutan Tampilan (Sort Order)
                        </Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) =>
                                setData(
                                    'sort_order',
                                    parseInt(e.target.value, 10) || 0,
                                )
                            }
                            className="mt-1 w-32"
                        />
                    </div>

                    <div className="flex items-center gap-3 self-center pt-3">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="size-4 rounded border-input text-primary focus:ring-primary"
                        />
                        <Label
                            htmlFor="is_active"
                            className="cursor-pointer text-sm"
                        >
                            Portal Aktif di SIMRS
                        </Label>
                    </div>
                </div>
            </div>

            {/* Section 2: Account Policy & Shared Credentials */}
            <div className="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-2.5 border-b border-border pb-2">
                    <Shield className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">
                            Kebijakan Akun & Autentikasi
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Tentukan apakah login memakai akun bersama RS atau
                            akun personal staf
                        </p>
                    </div>
                </div>

                <div className="max-w-md">
                    <Label htmlFor="auth_type">
                        Kebijakan Login Petugas{' '}
                        <span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={data.auth_type}
                        onValueChange={(val) =>
                            setData('auth_type', val as PortalAuthType)
                        }
                    >
                        <SelectTrigger className="mt-1">
                            <SelectValue placeholder="Pilih kebijakan login" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="both">
                                Akun Bersama RS & Akun Pribadi (Fleksibel /
                                Hybrid)
                            </SelectItem>
                            <SelectItem value="shared">
                                Hanya Akun Bersama RS (Single Institutional
                                Account)
                            </SelectItem>
                            <SelectItem value="personal">
                                Hanya Akun Pribadi Petugas (Personal Account
                                Only)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.auth_type && (
                        <p className="mt-1 text-xs text-destructive">
                            {errors.auth_type}
                        </p>
                    )}
                </div>

                {showSharedSection && (
                    <div className="space-y-4 rounded-xl border border-border/80 bg-muted/30 p-4">
                        <div className="flex items-center gap-2">
                            <Lock className="size-4 text-amber-500" />
                            <h3 className="text-sm font-semibold text-foreground">
                                Kredensial Bersama Tingkat Rumah Sakit
                            </h3>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Kredensial ini disimpan terenkripsi di database dan
                            diinjeksi ke browser staf yang berwenang.
                        </p>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="shared_username">
                                    Username Bersama RS
                                </Label>
                                <Input
                                    id="shared_username"
                                    value={data.shared_username}
                                    onChange={(e) =>
                                        setData(
                                            'shared_username',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Username akun instansi..."
                                    className="mt-1"
                                />
                                {errors.shared_username && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.shared_username}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="shared_password">
                                    Password Bersama RS
                                    {isEditing &&
                                        initialData?.has_shared_password && (
                                            <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                                (Tersimpan terenkripsi.
                                                Kosongkan jika tidak diubah)
                                            </span>
                                        )}
                                </Label>
                                <div className="relative mt-1">
                                    <Input
                                        id="shared_password"
                                        type={
                                            showPassword ? 'text' : 'password'
                                        }
                                        value={data.shared_password}
                                        onChange={(e) =>
                                            setData(
                                                'shared_password',
                                                e.target.value,
                                            )
                                        }
                                        placeholder={
                                            isEditing &&
                                            initialData?.has_shared_password
                                                ? '••••••••••••'
                                                : 'Ketik password akun bersama...'
                                        }
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowPassword(!showPassword)
                                        }
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        aria-label={
                                            showPassword
                                                ? 'Sembunyikan password'
                                                : 'Tampilkan password'
                                        }
                                    >
                                        {showPassword ? (
                                            <EyeOff className="size-4" />
                                        ) : (
                                            <Eye className="size-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.shared_password && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.shared_password}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Section 3: Login Form Selector Config */}
            <FormConfigEditor
                value={data.form_config}
                onChange={(newConfig) => setData('form_config', newConfig)}
            />

            {/* Actions */}
            <div className="flex items-center justify-between border-t border-border pt-4">
                <Button asChild variant="outline">
                    <Link href="/admin/portals" className="gap-2">
                        <ArrowLeft className="size-4" /> Batal & Kembali
                    </Link>
                </Button>

                <Button type="submit" disabled={processing} className="gap-2">
                    <Check className="size-4" />
                    {processing ? 'Menyimpan...' : 'Simpan'}
                </Button>
            </div>
        </form>
    );
}
