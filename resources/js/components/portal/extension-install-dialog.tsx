import { AlertCircle, Chrome, Download, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface ExtensionInstallDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ExtensionInstallDialog({
    open,
    onOpenChange,
}: ExtensionInstallDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100">
                        <Chrome className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        Panduan Pemasangan Ekstensi Browser SIFAST
                    </DialogTitle>
                    <DialogDescription className="text-sm text-slate-600 dark:text-slate-400">
                        Ekstensi browser resmi RS Aisyiyah Siti Fatimah
                        diperlukan agar SIMRS dapat mengisi username & password
                        ke website pelaporan eksternal secara otomatis dan aman.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    {/* Security Notice */}
                    <div className="flex items-start gap-3 rounded-lg border border-teal-200 bg-teal-50/60 p-3 text-xs text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-300">
                        <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" />
                        <div>
                            <p className="font-semibold">
                                Keamanan Zero-Persistence:
                            </p>
                            <p className="mt-0.5 opacity-90">
                                Ekstensi ini tidak menyimpan password di browser
                                Anda. Kredensial hanya dikirim saat Anda
                                mengklik tombol buka portal dan langsung dihapus
                                dari memori RAM setelah form terisi.
                            </p>
                        </div>
                    </div>

                    {/* Step-by-step instructions */}
                    <div className="space-y-3 text-sm">
                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                1
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Unduh dan Ekstrak Berkas Ekstensi
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Dapatkan folder ekstensi dari repositori
                                    proyek (
                                    <code className="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">
                                        rs-extension/
                                    </code>
                                    ) atau hubungi Tim IT SIMRS untuk
                                    mendapatkan paket berkas ZIP resmi.
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                2
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Buka Halaman Ekstensi Browser
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Buka browser berbasis Chromium (Google
                                    Chrome, Microsoft Edge, atau Brave), lalu
                                    buka alamat:
                                    <code className="ml-1 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-emerald-700 select-all dark:bg-slate-800 dark:text-emerald-400">
                                        chrome://extensions
                                    </code>
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                3
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Aktifkan Mode Pengembang & Muat Ekstensi
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Aktifkan tombol{' '}
                                    <strong>Developer mode</strong> (Mode
                                    pengembang) di pojok kanan atas, lalu klik{' '}
                                    <strong>Load unpacked</strong> (Muat yang
                                    belum dibongkar) dan pilih folder ekstensi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                        <div className="flex items-center gap-2 font-medium text-slate-900 dark:text-slate-200">
                            <AlertCircle className="h-4 w-4 text-slate-500" />
                            <span>
                                Tetap Bisa Mengakses Portal Tanpa Ekstensi:
                            </span>
                        </div>
                        <p className="mt-1">
                            Bila ekstensi belum dipasang, tombol{' '}
                            <strong>"Buka Portal"</strong> akan tetap membuka
                            website pelaporan di tab baru, namun Anda perlu
                            mengetikkan username & password secara manual.
                        </p>
                    </div>
                </div>

                <DialogFooter className="gap-2 sm:gap-2">
                    <a
                        href="/downloads/sifast-autofill-extension.zip"
                        download="sifast-autofill-extension.zip"
                        className="w-full sm:w-auto"
                    >
                        <Button
                            type="button"
                            className="w-full bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600"
                        >
                            <Download className="mr-1.5 h-4 w-4" />
                            Unduh Paket Ekstensi (.zip)
                        </Button>
                    </a>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Tutup Panduan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
