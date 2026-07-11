import { usePage } from '@inertiajs/react';
import {
    Eye,
    Monitor,
    Pencil,
    RotateCw,
    Smartphone,
    Tablet,
} from 'lucide-react';
import { useState } from 'react';

import { BlockRenderer } from '@/blocks/block-renderer';
import { applyLivePreviews, VisualBlocks } from '@/blocks/visual-editor';
import type { PreviewData } from '@/blocks/visual-editor';
import { PublicShell } from '@/components/cms/public-shell';
import { cn } from '@/lib/utils';
import type { Block } from '@/types/models';

type Device = 'desktop' | 'tablet' | 'mobile';

const DEVICES: {
    key: Device;
    label: string;
    icon: typeof Monitor;
    width: string;
}[] = [
    { key: 'desktop', label: 'Desktop', icon: Monitor, width: '100%' },
    { key: 'tablet', label: 'Tablet', icon: Tablet, width: '820px' },
    { key: 'mobile', label: 'Mobile', icon: Smartphone, width: '390px' },
];

/**
 * The page as it will look on the live site, with a device-width switcher and an
 * Edit / Preview toggle. Edit renders the blocks editable inside a public-layout
 * shell; Preview loads the actual published/draft page in an iframe (the real
 * route + layout + any future theme), so it can never drift from the live site.
 * Stays in the admin as a draft/staging surface.
 */
export function PageCanvas({
    value,
    onChange,
    title,
    showTitle = true,
}: {
    value: Block[];
    onChange: (blocks: Block[]) => void;
    title?: string;
    showTitle?: boolean;
}) {
    const [device, setDevice] = useState<Device>('desktop');
    const [editing, setEditing] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);
    const [saving, setSaving] = useState(false);
    const props = usePage().props;
    const previews = (props.blockPreviews as PreviewData | null) ?? null;
    const previewUrl = (props.previewUrl as string | null) ?? null;
    const blocksUrl = (props.blocksUrl as string | null) ?? null;
    const width = DEVICES.find((d) => d.key === device)?.width ?? '100%';
    const iframePreview = !editing && Boolean(previewUrl);

    // Persist the current blocks as a draft so the preview iframe (which renders
    // the saved page) reflects the latest edits. Returns once saved.
    const saveDraft = async () => {
        if (!blocksUrl) {
            return;
        }

        const csrf = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
        );
        setSaving(true);

        try {
            await fetch(blocksUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ blocks: value }),
            });
        } finally {
            setSaving(false);
        }
    };

    // Switch to the preview iframe, saving the draft first so new edits show.
    const showPreview = async () => {
        if (previewUrl) {
            await saveDraft();
        }

        setEditing(false);
        setReloadKey((k) => k + 1);
    };

    return (
        <div className="rounded-lg border bg-background">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b px-3 py-2">
                <div className="flex items-center gap-1 rounded-md border p-0.5">
                    {DEVICES.map((d) => (
                        <button
                            key={d.key}
                            type="button"
                            title={d.label}
                            onClick={() => setDevice(d.key)}
                            className={cn(
                                'rounded p-1.5 transition',
                                device === d.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <d.icon className="size-4" />
                        </button>
                    ))}
                </div>

                <div className="flex items-center gap-2">
                    {iframePreview && (
                        <button
                            type="button"
                            onClick={showPreview}
                            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            title="Save the draft and reload the preview"
                        >
                            <RotateCw
                                className={cn(
                                    'size-3.5',
                                    saving && 'animate-spin',
                                )}
                            />{' '}
                            Refresh
                        </button>
                    )}
                    <span className="hidden text-xs text-muted-foreground sm:inline">
                        {saving
                            ? 'Saving draft…'
                            : editing
                              ? 'Click text to edit · saved as draft until you publish'
                              : iframePreview
                                ? 'Preview of your saved draft'
                                : 'Read-only preview'}
                    </span>
                    <div className="flex items-center gap-1 rounded-md border p-0.5 text-sm">
                        <button
                            type="button"
                            onClick={() => setEditing(true)}
                            className={cn(
                                'flex items-center gap-1.5 rounded px-2.5 py-1 transition',
                                editing
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <Pencil className="size-3.5" /> Edit
                        </button>
                        <button
                            type="button"
                            onClick={showPreview}
                            className={cn(
                                'flex items-center gap-1.5 rounded px-2.5 py-1 transition',
                                !editing
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <Eye className="size-3.5" /> Preview
                        </button>
                    </div>
                </div>
            </div>

            <div
                className={cn(
                    'overflow-auto bg-neutral-200 dark:bg-neutral-800',
                    iframePreview ? 'p-4' : 'max-h-[74vh] p-4',
                )}
            >
                <div
                    className="canvas-light mx-auto min-h-full bg-white text-neutral-900 shadow-sm transition-[max-width] duration-200"
                    style={{ maxWidth: width }}
                >
                    {editing ? (
                        <PublicShell title={title} showTitle={showTitle}>
                            <VisualBlocks value={value} onChange={onChange} />
                        </PublicShell>
                    ) : iframePreview ? (
                        <iframe
                            key={reloadKey}
                            src={previewUrl ?? undefined}
                            title="Page preview"
                            className="block h-[72vh] w-full border-0 bg-white"
                        />
                    ) : (
                        <PublicShell title={title} showTitle={showTitle}>
                            <BlockRenderer
                                blocks={applyLivePreviews(value, previews)}
                            />
                        </PublicShell>
                    )}
                </div>
            </div>
        </div>
    );
}
