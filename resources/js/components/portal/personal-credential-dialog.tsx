import { Eye, EyeOff, KeyRound, Loader2, Save, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PortalCardItem } from '@/types/portal';

interface PersonalCredentialDialogProps {
    portal: PortalCardItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSuccess?: () => void;
}

export function PersonalCredentialDialog({
    portal,
    open,
    onOpenChange,
    onSuccess,
}: PersonalCredentialDialogProps) {
    const [username, setUsername] = useState(portal?.personal_username || '');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    // Sinkronisasi form saat modal dibuka atau portal yang dipilih berganti
    useEffect(() => {
        if (open && portal) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setUsername(portal.personal_username || '');
            setPassword('');
            setShowPassword(false);
            setErrorMessage(null);
            setSuccessMessage(null);
        }
    }, [open, portal]);

    const handleOpenChange = (newOpen: boolean) => {
        if (newOpen && portal) {
            setUsername(portal.personal_username || '');
            setPassword('');
            setShowPassword(false);
            setErrorMessage(null);
            setSuccessMessage(null);
        }
        onOpenChange(newOpen);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!portal) return;

        if (!username.trim()) {
            setErrorMessage('Username akun pribadi wajib diisi.');
            return;
        }

        if (!portal.has_personal_credential && !password.trim()) {
            setErrorMessage('Password akun pribadi wajib diisi untuk konfigurasi awal.');
            return;
        }

        setIsLoading(true);
        setErrorMessage(null);
        setSuccessMessage(null);

        try {
            // Ambil CSRF token dari meta tag bawaan Laravel
            const tokenMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
            const csrfToken = tokenMeta ? tokenMeta.content : '';

            const response = await fetch(`/portal-pelaporan/${portal.id}/personal-credentials`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    username: username.trim(),
                    ...(password ? { password } : {}),
                }),
            });

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                const message =
                    data?.message ||
                    (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Gagal menyimpan kredensial.');
                throw new Error(message);
            }

            setSuccessMessage('Kredensial akun pribadi berhasil disimpan.');
            setTimeout(() => {
                onOpenChange(false);
                if (onSuccess) {
                    onSuccess();
                }
            }, 800);
        } catch (err: unknown) {
            const msg = err instanceof Error ? err.message : 'Terjadi kesalahan saat menyimpan data.';
            setErrorMessage(msg);
        } finally {
            setIsLoading(false);
        }
    };

    if (!portal || !portal.can_configure_personal) return null;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100">
                            <KeyRound className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            Atur Akun Pribadi: {portal.name}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-slate-600 dark:text-slate-400">
                            Masukkan username dan password akun pribadi Anda pada portal ini. Data Anda disimpan secara terenkripsi aman di SIMRS.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {errorMessage && (
                            <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-400">
                                {errorMessage}
                            </div>
                        )}

                        {successMessage && (
                            <div className="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400">
                                <ShieldCheck className="h-4 w-4 shrink-0" />
                                <span>{successMessage}</span>
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <Label htmlFor="personal_username" className="text-xs font-semibold">
                                Username / NIK / Email Pribadi <span className="text-rose-500">*</span>
                            </Label>
                            <Input
                                id="personal_username"
                                type="text"
                                placeholder="Contoh: user.kemenkes@gmail.com"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                required
                                disabled={isLoading}
                                className="h-9"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="personal_password" className="text-xs font-semibold">
                                    Password Akun Pribadi {!portal.has_personal_credential && <span className="text-rose-500">*</span>}
                                </Label>
                                {portal.has_personal_credential && (
                                    <span className="text-[11px] text-slate-500">
                                        (Kosongkan jika tidak ingin mengubah)
                                    </span>
                                )}
                            </div>
                            <div className="relative">
                                <Input
                                    id="personal_password"
                                    type={showPassword ? 'text' : 'password'}
                                    placeholder={portal.has_personal_credential ? '••••••••••••' : 'Masukkan password portal'}
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    disabled={isLoading}
                                    required={!portal.has_personal_credential}
                                    className="h-9 pr-9 font-mono"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    tabIndex={-1}
                                    aria-label={showPassword ? 'Sembunyikan password' : 'Lihat password'}
                                >
                                    {showPassword ? (
                                        <EyeOff className="h-4 w-4" />
                                    ) : (
                                        <Eye className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                Password ini akan otomatis diisi oleh ekstensi browser saat Anda membuka portal.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={isLoading}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={isLoading}
                            className="gap-1.5 bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                        >
                            {isLoading ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span>Menyimpan...</span>
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" />
                                    <span>Simpan Akun</span>
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
