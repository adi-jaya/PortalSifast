import { useForm } from '@inertiajs/react';
import { FileDown, Upload } from 'lucide-react';
import { FormEvent, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    tipe: 'ruang' | 'aspak' | 'non_alkes';
};

export function MasterCsvActions({ tipe }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!data.file) {
            return;
        }
        post(`/aset/master/${tipe}/csv/import`, {
            forceFormData: true,
            onSuccess: () => {
                reset('file');
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    };

    return (
        <div className="space-y-2 rounded-xl border bg-card p-4">
            <p className="text-xs text-muted-foreground">CSV UTF-8 · upsert by kode / id katalog</p>
            <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" asChild>
                    <a href={`/aset/master/${tipe}/csv/template`}>
                        <FileDown className="mr-1.5 h-3.5 w-3.5" />
                        Template
                    </a>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <a href={`/aset/master/${tipe}/csv/export`}>
                        <FileDown className="mr-1.5 h-3.5 w-3.5" />
                        Export CSV
                    </a>
                </Button>
                <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
                    <Input
                        ref={fileRef}
                        type="file"
                        accept=".csv,text/csv"
                        className="h-8 max-w-[220px] cursor-pointer text-xs"
                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    />
                    <Button
                        type="submit"
                        size="sm"
                        disabled={processing || !data.file}
                        className="bg-teal-700 hover:bg-teal-800"
                    >
                        <Upload className="mr-1.5 h-3.5 w-3.5" />
                        {processing ? 'Import...' : 'Import CSV'}
                    </Button>
                </form>
            </div>
            <InputError message={errors.file} />
        </div>
    );
}
