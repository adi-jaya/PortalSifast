import { Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Eye,
    EyeOff,
    Globe,
    Lock,
    Shield,
    Sparkles,
} from 'lucide-react';
import React, { useState } from 'react';
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

    const { data, setData, post, put, processing, errors } = useForm({
        name: initialData?.name ?? '',
        slug: initialData?.slug ?? '',
        category: initialData?.category ?? (categories[0] || 'Kemenkes'),
        url: initialData?.url ?? '',
        url_pattern: initialData?.url_pattern ?? '',
        icon_path: initialData?.icon_path ?? '',
        description: initialData?.description ?? '',
        auth_type: (initialData?.auth_type ?? 'both') as PortalAuthType,
        shared_username: initialData?.shared_username ?? '',
        shared_password: '',
        form_config: (initialData?.form_config ??
            defaultFormConfig) as FormConfig,
        is_active: initialData?.is_active ?? true,
        sort_order: initialData?.sort_order ?? 0,
    });

    const handleAutoSlug = (force = false) => {
        if (!data.name) return;
        if (!force && (isEditing || data.slug)) return;
        const slugified = data.name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-');
        setData('slug', slugified);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing && initialData?.id) {
            put(`/admin/portals/${initialData.id}`);
        } else {
            post('/admin/portals');
        }
    };

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

                    <div>
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

                    <div>
                        <Label htmlFor="icon_path">Logo / Path Ikon</Label>
                        <Input
                            id="icon_path"
                            value={data.icon_path}
                            onChange={(e) =>
                                setData('icon_path', e.target.value)
                            }
                            placeholder="Contoh: /images/portals/kemenkes.png"
                            className="mt-1 text-xs"
                        />
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
                    {isEditing
                        ? 'Simpan Perubahan Portal'
                        : 'Simpan & Tambah Portal'}
                </Button>
            </div>
        </form>
    );
}
