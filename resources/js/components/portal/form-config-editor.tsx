import { AlertCircle, Code, Eye, Plus, Trash2 } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { FormConfig } from '@/types';
import { SelectorTagInput } from './selector-tag-input';

interface FormConfigEditorProps {
    value?: FormConfig | null;
    onChange: (config: FormConfig) => void;
}

const DEFAULT_CONFIG: FormConfig = {
    is_spa: false,
    wait_timeout_ms: 10000,
    username_field: { selectors: ["input[name='username']", '#username'] },
    password_field: { selectors: ["input[name='password']", '#password'] },
    extra_fields: [],
    auto_submit: false,
};

export function FormConfigEditor({ value, onChange }: FormConfigEditorProps) {
    const config = value ?? DEFAULT_CONFIG;
    const [rawJson, setRawJson] = useState<string>(() =>
        JSON.stringify(config, null, 2),
    );
    const [jsonError, setJsonError] = useState<string | null>(null);
    const [tab, setTab] = useState<'visual' | 'raw'>('visual');

    const handleVisualUpdate = (partial: Partial<FormConfig>) => {
        const updated = { ...config, ...partial };
        onChange(updated);
        setRawJson(JSON.stringify(updated, null, 2));
        setJsonError(null);
    };

    const handleTabChange = (newTab: 'visual' | 'raw') => {
        if (newTab === 'raw') {
            setRawJson(JSON.stringify(config, null, 2));
            setJsonError(null);
        }
        setTab(newTab);
    };

    const handleRawJsonChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
        const text = e.target.value;
        setRawJson(text);
        try {
            const parsed = JSON.parse(text);
            if (
                typeof parsed !== 'object' ||
                parsed === null ||
                Array.isArray(parsed)
            ) {
                setJsonError(
                    'Konfigurasi harus berupa object JSON valid ({}).',
                );
                return;
            }
            setJsonError(null);
            onChange(parsed as FormConfig);
        } catch (err: unknown) {
            setJsonError((err as Error).message);
        }
    };

    const handleAddExtraField = () => {
        const newExtra = [
            ...(config.extra_fields || []),
            {
                key: `field_${(config.extra_fields || []).length + 1}`,
                selectors: [],
            },
        ];
        handleVisualUpdate({ extra_fields: newExtra });
    };

    const handleRemoveExtraField = (index: number) => {
        const updated = (config.extra_fields || []).filter(
            (_, i) => i !== index,
        );
        handleVisualUpdate({ extra_fields: updated });
    };

    const handleExtraFieldKeyChange = (index: number, newKey: string) => {
        const updated = [...(config.extra_fields || [])];
        updated[index] = { ...updated[index], key: newKey };
        handleVisualUpdate({ extra_fields: updated });
    };

    const handleExtraFieldSelectorsChange = (
        index: number,
        selectors: string[],
    ) => {
        const updated = [...(config.extra_fields || [])];
        updated[index] = { ...updated[index], selectors };
        handleVisualUpdate({ extra_fields: updated });
    };

    return (
        <div className="space-y-4 rounded-xl border border-border bg-card p-4">
            <div className="flex flex-col gap-2 border-b border-border pb-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-foreground">
                        Konfigurasi Form Selector Login
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Pengaturan selector DOM untuk injeksi ekstensi browser
                        autofill
                    </p>
                </div>
                <Tabs
                    value={tab}
                    onValueChange={(v) =>
                        handleTabChange(v as 'visual' | 'raw')
                    }
                >
                    <TabsList className="h-8">
                        <TabsTrigger value="visual" className="gap-1.5 text-xs">
                            <Eye className="size-3.5" /> Visual Builder
                        </TabsTrigger>
                        <TabsTrigger value="raw" className="gap-1.5 text-xs">
                            <Code className="size-3.5" /> Raw JSON
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            {tab === 'visual' ? (
                <div className="space-y-5">
                    {/* General Form Toggles */}
                    <div className="grid grid-cols-1 gap-4 rounded-lg border border-border/60 bg-muted/20 p-3 md:grid-cols-3">
                        <div className="flex items-center gap-3">
                            <input
                                id="is_spa"
                                type="checkbox"
                                checked={config.is_spa}
                                onChange={(e) =>
                                    handleVisualUpdate({
                                        is_spa: e.target.checked,
                                    })
                                }
                                className="size-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <div>
                                <Label
                                    htmlFor="is_spa"
                                    className="cursor-pointer text-xs font-semibold"
                                >
                                    SPA Mode (React / Vue)
                                </Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Tunggu elemen dirender dinamis di browser
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label
                                htmlFor="wait_timeout"
                                className="text-xs font-semibold"
                            >
                                Timeout Menunggu (ms)
                            </Label>
                            <Input
                                id="wait_timeout"
                                type="number"
                                min={1000}
                                max={60000}
                                step={500}
                                value={config.wait_timeout_ms || 10000}
                                onChange={(e) =>
                                    handleVisualUpdate({
                                        wait_timeout_ms:
                                            parseInt(e.target.value, 10) ||
                                            10000,
                                    })
                                }
                                className="mt-1 h-8 text-xs"
                            />
                        </div>

                        <div className="flex items-center gap-3">
                            <input
                                id="auto_submit"
                                type="checkbox"
                                checked={config.auto_submit}
                                onChange={(e) =>
                                    handleVisualUpdate({
                                        auto_submit: e.target.checked,
                                    })
                                }
                                className="size-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <div>
                                <Label
                                    htmlFor="auto_submit"
                                    className="cursor-pointer text-xs font-semibold"
                                >
                                    Auto Submit Form
                                </Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Nonaktifkan jika website memiliki CAPTCHA
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Username Selector */}
                    <SelectorTagInput
                        label="Selector Bidang Username / Email"
                        selectors={config.username_field?.selectors || []}
                        onChange={(selectors) =>
                            handleVisualUpdate({
                                username_field: { selectors },
                            })
                        }
                        presets={[
                            '#username',
                            '#email',
                            '#c',
                            "input[name='username']",
                            "input[name='email']",
                            "input[type='email']",
                        ]}
                        placeholder="Contoh: #username atau input[name='email']"
                    />

                    {/* Password Selector */}
                    <SelectorTagInput
                        label="Selector Bidang Password"
                        selectors={config.password_field?.selectors || []}
                        onChange={(selectors) =>
                            handleVisualUpdate({
                                password_field: { selectors },
                            })
                        }
                        presets={[
                            '#password',
                            '#pass',
                            "input[name='password']",
                            "input[name='pwd']",
                            "input[type='password']",
                        ]}
                        placeholder="Contoh: #password atau input[name='password']"
                    />

                    {/* Extra Fields */}
                    <div className="space-y-3 border-t border-border pt-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label className="text-xs font-semibold">
                                    Field Tambahan (Kode Satker / Fasyankes /
                                    Captcha Info)
                                </Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Tambahkan field jika website membutuhkan
                                    input identitas selain username dan password
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleAddExtraField}
                                className="h-7 text-xs"
                            >
                                <Plus className="mr-1 size-3" /> Tambah Field
                            </Button>
                        </div>

                        {(config.extra_fields || []).map((field, fIdx) => (
                            <div
                                key={fIdx}
                                className="relative space-y-2 rounded-lg border border-border bg-muted/20 p-3"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex-1">
                                        <Label className="text-[11px] text-muted-foreground">
                                            Nama Kunci (Key)
                                        </Label>
                                        <Input
                                            type="text"
                                            value={field.key}
                                            onChange={(e) =>
                                                handleExtraFieldKeyChange(
                                                    fIdx,
                                                    e.target.value,
                                                )
                                            }
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter')
                                                    e.preventDefault();
                                            }}
                                            placeholder="misal: kode_satker"
                                            className="mt-0.5 h-8 font-mono text-xs"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            handleRemoveExtraField(fIdx)
                                        }
                                        aria-label={`Hapus field ${field.key || fIdx + 1}`}
                                        className="size-8 shrink-0 self-end text-destructive hover:bg-destructive/10"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                                <SelectorTagInput
                                    selectors={field.selectors || []}
                                    onChange={(selectors) =>
                                        handleExtraFieldSelectorsChange(
                                            fIdx,
                                            selectors,
                                        )
                                    }
                                    placeholder={`Selector untuk field ${field.key}...`}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <div className="space-y-2">
                    <Textarea
                        value={rawJson}
                        onChange={handleRawJsonChange}
                        rows={14}
                        className="font-mono text-xs leading-relaxed"
                        placeholder="Masukkan JSON form_config..."
                    />
                    {jsonError && (
                        <div className="flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 p-2 text-xs text-destructive">
                            <AlertCircle className="size-4 shrink-0" />
                            <span>Syntax JSON Tidak Valid: {jsonError}</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
