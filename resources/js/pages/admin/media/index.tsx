import { Head, router, useForm } from '@inertiajs/react';
import { FileText, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import type { MediaAsset, Paginated } from '@/types/models';

export default function MediaIndex({
    items,
    filters,
}: {
    items: Paginated<MediaAsset>;
    filters: { search?: string; type?: string };
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const first = useRef(true);

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const t = setTimeout(() => {
            router.get(
                '/admin/media',
                cleaned({ search, type: filters.type }),
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const setType = (type: string) => {
        router.get('/admin/media', cleaned({ search, type }), {
            preserveState: true,
            replace: true,
        });
    };

    const uploadForm = useForm<{
        file: File | null;
        alt_text: string;
        caption: string;
        credit: string;
    }>({
        file: null,
        alt_text: '',
        caption: '',
        credit: '',
    });

    const [editing, setEditing] = useState<MediaAsset | null>(null);

    const submitUpload = (e: FormEvent) => {
        e.preventDefault();
        uploadForm.post('/admin/media', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => uploadForm.reset(),
        });
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="Media library" />
            <PageHeader
                title="Media library"
                description="Upload and manage images and documents used across the site."
            />

            <form
                onSubmit={submitUpload}
                className="space-y-3 rounded-lg border p-4"
            >
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2 sm:col-span-2 lg:col-span-1">
                        <Label htmlFor="file">File</Label>
                        <Input
                            id="file"
                            type="file"
                            onChange={(e) =>
                                uploadForm.setData(
                                    'file',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {uploadForm.errors.file && (
                            <p className="text-sm text-destructive">
                                {uploadForm.errors.file}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="alt_text">Alt text</Label>
                        <Input
                            id="alt_text"
                            value={uploadForm.data.alt_text}
                            onChange={(e) =>
                                uploadForm.setData('alt_text', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="caption">Caption</Label>
                        <Input
                            id="caption"
                            value={uploadForm.data.caption}
                            onChange={(e) =>
                                uploadForm.setData('caption', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="credit">Credit</Label>
                        <Input
                            id="credit"
                            value={uploadForm.data.credit}
                            onChange={(e) =>
                                uploadForm.setData('credit', e.target.value)
                            }
                        />
                    </div>
                </div>
                <Button type="submit" disabled={uploadForm.processing}>
                    <Upload className="size-4" /> Upload
                </Button>
            </form>

            <div className="flex flex-wrap items-center gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search media…"
                    className="max-w-xs"
                />
                <Select
                    value={filters.type || 'all'}
                    onValueChange={(v) => setType(v === 'all' ? '' : v)}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All types</SelectItem>
                        <SelectItem value="image">Image</SelectItem>
                        <SelectItem value="document">Document</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                {items.data.map((asset) => (
                    <button
                        key={asset.id}
                        type="button"
                        onClick={() => setEditing(asset)}
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
                                <FileText className="size-8 text-muted-foreground" />
                            )}
                        </div>
                        <div className="p-2">
                            <p className="truncate text-xs font-medium">
                                {asset.original_name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Used by {asset.references_count ?? 0}
                            </p>
                        </div>
                    </button>
                ))}
                {items.data.length === 0 && (
                    <p className="col-span-full py-8 text-center text-sm text-muted-foreground">
                        No media yet.
                    </p>
                )}
            </div>

            <Pagination meta={items} />

            {editing && (
                <EditMediaDialog
                    asset={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </div>
    );
}

function EditMediaDialog({
    asset,
    onClose,
}: {
    asset: MediaAsset;
    onClose: () => void;
}) {
    const form = useForm({
        alt_text: asset.alt_text ?? '',
        caption: asset.caption ?? '',
        credit: asset.credit ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/media/${asset.id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(v) => !v && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{asset.original_name}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-3">
                    <div className="space-y-2">
                        <Label htmlFor="edit_alt_text">Alt text</Label>
                        <Input
                            id="edit_alt_text"
                            value={form.data.alt_text}
                            onChange={(e) =>
                                form.setData('alt_text', e.target.value)
                            }
                        />
                        {form.errors.alt_text && (
                            <p className="text-sm text-destructive">
                                {form.errors.alt_text}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit_caption">Caption</Label>
                        <Textarea
                            id="edit_caption"
                            value={form.data.caption}
                            onChange={(e) =>
                                form.setData('caption', e.target.value)
                            }
                        />
                        {form.errors.caption && (
                            <p className="text-sm text-destructive">
                                {form.errors.caption}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit_credit">Credit</Label>
                        <Input
                            id="edit_credit"
                            value={form.data.credit}
                            onChange={(e) =>
                                form.setData('credit', e.target.value)
                            }
                        />
                        {form.errors.credit && (
                            <p className="text-sm text-destructive">
                                {form.errors.credit}
                            </p>
                        )}
                    </div>
                    <DialogFooter className="sm:justify-between">
                        <ConfirmDialog
                            trigger={
                                <Button type="button" variant="ghost">
                                    Delete
                                </Button>
                            }
                            title={`Delete “${asset.original_name}”?`}
                            description="Media in use by pages, programs, or other content cannot be deleted."
                            confirmLabel="Delete"
                            destructive
                            onConfirm={() =>
                                router.delete(`/admin/media/${asset.id}`, {
                                    preserveScroll: true,
                                    onSuccess: onClose,
                                })
                            }
                        />
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function cleaned(obj: Record<string, string | undefined>) {
    return Object.fromEntries(Object.entries(obj).filter(([, v]) => v));
}
