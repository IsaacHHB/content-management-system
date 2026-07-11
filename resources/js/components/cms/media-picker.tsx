import { ImageIcon, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { MediaAsset } from '@/types/models';

async function fetchMedia(search: string, type: string): Promise<MediaAsset[]> {
    const params = new URLSearchParams();

    if (search) {
        params.set('search', search);
    }

    if (type) {
        params.set('type', type);
    }

    const res = await fetch(`/admin/media?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!res.ok) {
        return [];
    }

    const json = await res.json();

    return json.data ?? [];
}

export function MediaPicker({
    open,
    onOpenChange,
    onSelect,
    type = 'image',
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    onSelect: (asset: MediaAsset) => void;
    type?: 'image' | 'document' | '';
}) {
    const [items, setItems] = useState<MediaAsset[]>([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let active = true;
        const t = setTimeout(() => {
            setLoading(true);
            fetchMedia(search, type).then((m) => {
                if (!active) {
                    return;
                }

                setItems(m);
                setLoading(false);
            });
        }, 250);

        return () => {
            active = false;
            clearTimeout(t);
        };
    }, [open, search, type]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Choose media</DialogTitle>
                </DialogHeader>
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search media…"
                />
                <div className="grid max-h-96 grid-cols-3 gap-3 overflow-y-auto sm:grid-cols-4">
                    {loading && (
                        <p className="col-span-full text-sm text-muted-foreground">
                            Loading…
                        </p>
                    )}
                    {!loading && items.length === 0 && (
                        <p className="col-span-full text-sm text-muted-foreground">
                            No media found. Upload files in the Media library
                            first.
                        </p>
                    )}
                    {items.map((asset) => (
                        <button
                            key={asset.id}
                            type="button"
                            onClick={() => {
                                onSelect(asset);
                                onOpenChange(false);
                            }}
                            className="group flex flex-col overflow-hidden rounded-md border border-input text-left hover:border-primary"
                        >
                            <div className="flex aspect-square items-center justify-center bg-muted">
                                {asset.type === 'image' && asset.thumb_url ? (
                                    <img
                                        src={asset.thumb_url}
                                        alt={asset.alt_text ?? ''}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <ImageIcon className="size-8 text-muted-foreground" />
                                )}
                            </div>
                            <span className="truncate p-1 text-xs">
                                {asset.original_name}
                            </span>
                        </button>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}

export function MediaField({
    label,
    asset,
    onChange,
    type = 'image',
}: {
    label?: string;
    asset: MediaAsset | null;
    onChange: (asset: MediaAsset | null) => void;
    type?: 'image' | 'document' | '';
}) {
    const [open, setOpen] = useState(false);

    return (
        <div className="space-y-2">
            {label && <span className="text-sm font-medium">{label}</span>}
            <div className={cn('flex items-center gap-3')}>
                <div className="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                    {asset?.type === 'image' && asset.thumb_url ? (
                        <img
                            src={asset.thumb_url}
                            alt={asset.alt_text ?? ''}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <ImageIcon className="size-6 text-muted-foreground" />
                    )}
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setOpen(true)}
                    >
                        {asset ? 'Replace' : 'Choose'}
                    </Button>
                    {asset && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => onChange(null)}
                        >
                            <X className="size-4" /> Remove
                        </Button>
                    )}
                </div>
            </div>
            <MediaPicker
                open={open}
                onOpenChange={setOpen}
                onSelect={onChange}
                type={type}
            />
        </div>
    );
}
