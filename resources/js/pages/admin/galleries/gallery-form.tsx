import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, useForm } from '@inertiajs/react';
import { GripVertical, ImageIcon, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Card, FormRow, PublishPanel } from '@/components/cms/form-panels';
import type { FormLike } from '@/components/cms/form-panels';
import { MediaPicker } from '@/components/cms/media-picker';
import { PageHeader } from '@/components/cms/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { toDatetimeLocal } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Gallery, MediaAsset } from '@/types/models';

type PhotoRow = {
    asset: MediaAsset;
    alt_text: string;
    caption: string;
};

type GalleryFormData = {
    title: string;
    slug: string;
    description: string;
    status: string;
    published_at: string | null;
    media_assets: {
        id: number;
        alt_text: string;
        caption: string | null;
        sort_order: number;
    }[];
};

function initialPhotos(item?: Gallery): PhotoRow[] {
    return [...(item?.media_assets ?? [])]
        .sort((a, b) => a.pivot.sort_order - b.pivot.sort_order)
        .map((asset) => ({
            asset,
            alt_text: asset.pivot.alt_text,
            caption: asset.pivot.caption ?? '',
        }));
}

function toMediaAssets(photos: PhotoRow[]): GalleryFormData['media_assets'] {
    return photos.map((photo, index) => ({
        id: photo.asset.id,
        alt_text: photo.alt_text,
        caption: photo.caption || null,
        sort_order: index,
    }));
}

function SortablePhotoRow({
    photo,
    error,
    onChange,
    onRemove,
}: {
    photo: PhotoRow;
    error?: string;
    onChange: (data: Partial<Pick<PhotoRow, 'alt_text' | 'caption'>>) => void;
    onRemove: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: photo.asset.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'flex items-start gap-3 rounded-lg border bg-card p-3',
                isDragging && 'opacity-60 shadow-lg',
            )}
        >
            <button
                type="button"
                className="mt-4 cursor-grab text-muted-foreground"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="size-4" />
            </button>
            <div className="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                {photo.asset.thumb_url ? (
                    <img
                        src={photo.asset.thumb_url}
                        alt={photo.alt_text}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <ImageIcon className="size-6 text-muted-foreground" />
                )}
            </div>
            <div className="grid flex-1 gap-2 sm:grid-cols-2">
                <div className="space-y-1">
                    <Input
                        value={photo.alt_text}
                        onChange={(e) => onChange({ alt_text: e.target.value })}
                        placeholder="Alt text (required)"
                    />
                    {error && <InputError message={error} />}
                </div>
                <Input
                    value={photo.caption}
                    onChange={(e) => onChange({ caption: e.target.value })}
                    placeholder="Caption"
                />
            </div>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={onRemove}
            >
                <Trash2 className="size-4" />
            </Button>
        </div>
    );
}

export default function GalleryForm({ item }: { item?: Gallery }) {
    const isEdit = Boolean(item);
    const [photos, setPhotos] = useState<PhotoRow[]>(() => initialPhotos(item));
    const [pickerOpen, setPickerOpen] = useState(false);

    const form = useForm<GalleryFormData>({
        title: item?.title ?? '',
        slug: item?.slug ?? '',
        description: item?.description ?? '',
        status: item?.status ?? 'draft',
        published_at: toDatetimeLocal(item?.published_at),
        media_assets: toMediaAssets(photos),
    });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const updatePhotos = (next: PhotoRow[]) => {
        setPhotos(next);
        form.setData('media_assets', toMediaAssets(next));
    };

    const addPhoto = (asset: MediaAsset) => {
        if (photos.some((photo) => photo.asset.id === asset.id)) {
            return;
        }

        updatePhotos([
            ...photos,
            {
                asset,
                alt_text: asset.alt_text ?? '',
                caption: asset.caption ?? '',
            },
        ]);
    };

    const removePhoto = (id: number) =>
        updatePhotos(photos.filter((photo) => photo.asset.id !== id));

    const updatePhoto = (
        id: number,
        data: Partial<Pick<PhotoRow, 'alt_text' | 'caption'>>,
    ) =>
        updatePhotos(
            photos.map((photo) =>
                photo.asset.id === id ? { ...photo, ...data } : photo,
            ),
        );

    const onDragEnd = (e: DragEndEvent) => {
        const { active, over } = e;

        if (over && active.id !== over.id) {
            const oldIndex = photos.findIndex(
                (photo) => photo.asset.id === active.id,
            );
            const newIndex = photos.findIndex(
                (photo) => photo.asset.id === over.id,
            );
            updatePhotos(arrayMove(photos, oldIndex, newIndex));
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/galleries/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/galleries');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.title}` : 'New gallery'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit gallery' : 'New gallery'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/galleries">Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </PageHeader>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <FormRow label="Title" error={form.errors.title}>
                                <Input
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Slug"
                                error={form.errors.slug}
                                hint="Leave blank to auto-generate from the title."
                            >
                                <Input
                                    value={form.data.slug}
                                    onChange={(e) =>
                                        form.setData('slug', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Description"
                                error={form.errors.description}
                            >
                                <Textarea
                                    value={form.data.description}
                                    onChange={(e) =>
                                        form.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Photos">
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragEnd={onDragEnd}
                            >
                                <SortableContext
                                    items={photos.map(
                                        (photo) => photo.asset.id,
                                    )}
                                    strategy={verticalListSortingStrategy}
                                >
                                    <div className="space-y-2">
                                        {photos.map((photo, index) => (
                                            <SortablePhotoRow
                                                key={photo.asset.id}
                                                photo={photo}
                                                error={
                                                    (
                                                        form.errors as Record<
                                                            string,
                                                            string
                                                        >
                                                    )[
                                                        `media_assets.${index}.alt_text`
                                                    ]
                                                }
                                                onChange={(data) =>
                                                    updatePhoto(
                                                        photo.asset.id,
                                                        data,
                                                    )
                                                }
                                                onRemove={() =>
                                                    removePhoto(photo.asset.id)
                                                }
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>

                            {photos.length === 0 && (
                                <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                                    No photos yet.
                                </p>
                            )}

                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                onClick={() => setPickerOpen(true)}
                            >
                                <Plus className="size-4" /> Add photos
                            </Button>
                            <MediaPicker
                                open={pickerOpen}
                                onOpenChange={setPickerOpen}
                                onSelect={addPhoto}
                                type="image"
                            />
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <PublishPanel form={form as unknown as FormLike} />
                    </div>
                </div>
            </form>
        </div>
    );
}
