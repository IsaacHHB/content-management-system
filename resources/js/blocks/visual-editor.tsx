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
import { usePage } from '@inertiajs/react';
import {
    Copy,
    GripVertical,
    ImageUp,
    Plus,
    Settings2,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import { BLOCK_ORDER, BLOCKS } from '@/blocks/registry';
import { TiptapEditor } from '@/blocks/tiptap';
import { MediaPicker } from '@/components/cms/media-picker';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { Block, BlockType, MediaAsset, TiptapDoc } from '@/types/models';

function newId(): string {
    return 'b' + crypto.randomUUID().replace(/-/g, '').slice(0, 20);
}

// Blocks whose primary image can be swapped by clicking it in the canvas.
const IMAGE_BLOCKS: Partial<Record<BlockType, true>> = {
    image: true,
    hero: true,
    image_text: true,
};

// Hint shown on the placeholder of a block that has nothing to render yet.
const CONFIG_HINT: Partial<Record<BlockType, string>> = {
    image: 'Click to choose an image',
    image_text: 'Click to choose an image',
    hero: 'Click to add a hero image or heading',
    gallery_embed: 'Click to choose a gallery',
    video_embed: 'Click to add a video link',
};

/**
 * 'ok' = has something to render; 'empty' = needs configuring (show a
 * placeholder); 'pending' = configured but its data is hydrated server-side, so
 * the real preview only appears after saving.
 */
function blockState(block: Block): 'ok' | 'empty' | 'pending' {
    const d = block.data;

    switch (block.type) {
        case 'image':
        case 'image_text':
            return d.media_asset_id || d.media ? 'ok' : 'empty';
        case 'hero':
            return d.media_asset_id || d.media || d.heading ? 'ok' : 'empty';
        case 'video_embed':
            return d.url ? 'ok' : 'empty';
        case 'gallery_embed':
            if (d.gallery) {
                return 'ok';
            }

            return d.gallery_id ? 'pending' : 'empty';
        default:
            return 'ok';
    }
}

export type PreviewData = {
    events?: unknown[];
    posts?: unknown[];
    members?: unknown[];
    partners?: unknown[];
};

/** Fills a newly-added list block with sample content so it previews live. */
function withLivePreview(
    block: Block,
    previews: PreviewData | null,
): Record<string, unknown> {
    const d = block.data;
    const empty = (a: unknown) => !Array.isArray(a) || a.length === 0;

    if (!previews) {
        return d;
    }

    if (block.type === 'events_list' && empty(d.events)) {
        return { ...d, events: previews.events ?? [] };
    }

    if (block.type === 'news_list' && empty(d.posts)) {
        return { ...d, posts: previews.posts ?? [] };
    }

    if (block.type === 'team_grid' && empty(d.members)) {
        return { ...d, members: previews.members ?? [] };
    }

    if (block.type === 'partners' && empty(d.partners)) {
        return { ...d, partners: previews.partners ?? [] };
    }

    return d;
}

/** Batch version for read-only preview rendering. */
export function applyLivePreviews(
    blocks: Block[],
    previews: PreviewData | null,
): Block[] {
    return blocks.map((b) => ({ ...b, data: withLivePreview(b, previews) }));
}

function AddBlockButton({
    onAdd,
    label = 'Add block',
    subtle = false,
}: {
    onAdd: (type: BlockType) => void;
    label?: string;
    subtle?: boolean;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                {subtle ? (
                    <button
                        type="button"
                        className="group/add flex w-full items-center justify-center py-1"
                        title="Insert block here"
                    >
                        <span className="flex items-center gap-1 rounded-full border bg-background px-2 py-0.5 text-xs text-muted-foreground opacity-0 shadow-sm transition group-hover/add:opacity-100">
                            <Plus className="size-3" /> Insert
                        </span>
                    </button>
                ) : (
                    <Button type="button" variant="outline" className="w-full">
                        <Plus className="size-4" /> {label}
                    </Button>
                )}
            </PopoverTrigger>
            <PopoverContent className="w-64 p-2">
                <div className="grid grid-cols-2 gap-1">
                    {BLOCK_ORDER.map((type) => {
                        const def = BLOCKS[type];
                        const Icon = def.icon;

                        return (
                            <button
                                key={type}
                                type="button"
                                onClick={() => {
                                    onAdd(type);
                                    setOpen(false);
                                }}
                                className="flex items-center gap-2 rounded-md p-2 text-left text-sm hover:bg-accent"
                            >
                                <Icon className="size-4" />
                                {def.label}
                            </button>
                        );
                    })}
                </div>
            </PopoverContent>
        </Popover>
    );
}

function VisualBlock({
    block,
    previews,
    onChange,
    onDuplicate,
    onDelete,
}: {
    block: Block;
    previews: PreviewData | null;
    onChange: (data: Record<string, unknown>) => void;
    onDuplicate: () => void;
    onDelete: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: block.id });
    const def = BLOCKS[block.type];
    const inlineEditable = block.type === 'rich_text';
    const canReplaceImage = Boolean(IMAGE_BLOCKS[block.type]);
    const [showSettings, setShowSettings] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);

    if (!def) {
        return null;
    }

    const Editor = def.Editor;
    const Render = def.Render;
    const Icon = def.icon;
    const state = blockState(block);

    const replaceImage = (asset: MediaAsset) =>
        onChange({ ...block.data, media_asset_id: asset.id, media: asset });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'group relative outline-1 -outline-offset-1 outline-transparent transition hover:outline-primary/40 hover:outline-dashed',
                isDragging && 'opacity-60',
            )}
        >
            <div className="absolute top-2 right-2 z-10 flex items-center gap-0.5 rounded-md border bg-background/95 p-0.5 opacity-0 shadow-sm transition group-hover:opacity-100">
                <button
                    type="button"
                    className="cursor-grab rounded p-1 text-muted-foreground hover:bg-accent"
                    title="Drag to reorder"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="size-4" />
                </button>
                {canReplaceImage && (
                    <button
                        type="button"
                        className="rounded p-1 text-muted-foreground hover:bg-accent"
                        title="Replace image"
                        onClick={() => setPickerOpen(true)}
                    >
                        <ImageUp className="size-4" />
                    </button>
                )}
                {!inlineEditable && (
                    <button
                        type="button"
                        className={cn(
                            'rounded p-1 text-muted-foreground hover:bg-accent',
                            showSettings && 'bg-accent text-foreground',
                        )}
                        title="Edit block"
                        onClick={() => setShowSettings((s) => !s)}
                    >
                        <Settings2 className="size-4" />
                    </button>
                )}
                <button
                    type="button"
                    className="rounded p-1 text-muted-foreground hover:bg-accent"
                    title="Duplicate"
                    onClick={onDuplicate}
                >
                    <Copy className="size-4" />
                </button>
                <button
                    type="button"
                    className="rounded p-1 text-muted-foreground hover:bg-accent"
                    title="Delete"
                    onClick={onDelete}
                >
                    <Trash2 className="size-4" />
                </button>
            </div>

            {inlineEditable ? (
                <div className="mx-auto max-w-4xl px-4 py-4">
                    <TiptapEditor
                        seamless
                        value={block.data.content as TiptapDoc}
                        onChange={(doc) =>
                            onChange({ ...block.data, content: doc })
                        }
                    />
                </div>
            ) : (
                <>
                    {state !== 'ok' ? (
                        <button
                            type="button"
                            onClick={() =>
                                canReplaceImage
                                    ? setPickerOpen(true)
                                    : setShowSettings(true)
                            }
                            className="m-4 flex w-[calc(100%-2rem)] flex-col items-center justify-center gap-1.5 rounded-lg border-2 border-dashed border-neutral-300 bg-neutral-50 py-10 text-neutral-500 transition hover:border-neutral-400 hover:bg-neutral-100"
                        >
                            <Icon className="size-6" />
                            <span className="text-sm font-medium">
                                {def.label}
                            </span>
                            <span className="text-xs">
                                {state === 'pending'
                                    ? 'Selected — full preview appears after you save'
                                    : (CONFIG_HINT[block.type] ??
                                      'Click to configure this block')}
                            </span>
                        </button>
                    ) : (
                        <div className="relative">
                            <div className="pointer-events-none select-none">
                                <Render
                                    data={withLivePreview(block, previews)}
                                />
                            </div>
                            {canReplaceImage && (
                                <button
                                    type="button"
                                    onClick={() => setPickerOpen(true)}
                                    className="absolute inset-0 flex items-center justify-center bg-neutral-900/0 opacity-0 transition hover:bg-neutral-900/30 hover:opacity-100"
                                    title="Replace image"
                                >
                                    <span className="flex items-center gap-1.5 rounded-md bg-white/95 px-3 py-1.5 text-sm font-medium text-neutral-900 shadow">
                                        <ImageUp className="size-4" /> Replace
                                        image
                                    </span>
                                </button>
                            )}
                        </div>
                    )}
                    {showSettings && (
                        <div className="border-t bg-muted/40 p-4 text-foreground">
                            <p className="mb-2 text-xs font-medium text-muted-foreground">
                                {def.label} settings
                            </p>
                            <Editor data={block.data} onChange={onChange} />
                        </div>
                    )}
                </>
            )}

            {canReplaceImage && (
                <MediaPicker
                    open={pickerOpen}
                    onOpenChange={setPickerOpen}
                    onSelect={replaceImage}
                />
            )}
        </div>
    );
}

/**
 * The editable block stack (click-to-edit text, image replace, per-block
 * settings, drag-reorder, insert-between). Rendered bare so it can sit inside
 * the real page layout (see PageCanvas).
 */
export function VisualBlocks({
    value,
    onChange,
}: {
    value: Block[];
    onChange: (blocks: Block[]) => void;
}) {
    const blocks = value ?? [];
    const previews =
        (usePage().props.blockPreviews as PreviewData | null) ?? null;
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const onDragEnd = (e: DragEndEvent) => {
        const { active, over } = e;

        if (over && active.id !== over.id) {
            const oldIndex = blocks.findIndex((b) => b.id === active.id);
            const newIndex = blocks.findIndex((b) => b.id === over.id);
            onChange(arrayMove(blocks, oldIndex, newIndex));
        }
    };

    const makeBlock = (type: BlockType): Block => ({
        id: newId(),
        type,
        data: structuredClone(BLOCKS[type].defaultData),
    });

    const insertAt = (index: number, type: BlockType) =>
        onChange([
            ...blocks.slice(0, index),
            makeBlock(type),
            ...blocks.slice(index),
        ]);

    const updateBlock = (id: string, data: Record<string, unknown>) =>
        onChange(blocks.map((b) => (b.id === id ? { ...b, data } : b)));
    const deleteBlock = (id: string) =>
        onChange(blocks.filter((b) => b.id !== id));
    const duplicateBlock = (id: string) => {
        const idx = blocks.findIndex((b) => b.id === id);

        if (idx < 0) {
            return;
        }

        const copy = {
            ...blocks[idx],
            id: newId(),
            data: structuredClone(blocks[idx].data),
        };
        onChange([...blocks.slice(0, idx + 1), copy, ...blocks.slice(idx + 1)]);
    };

    return (
        <div>
            {blocks.length === 0 && (
                <p className="py-8 text-center text-sm text-neutral-400">
                    Empty page. Add your first block below.
                </p>
            )}

            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={onDragEnd}
            >
                <SortableContext
                    items={blocks.map((b) => b.id)}
                    strategy={verticalListSortingStrategy}
                >
                    {blocks.map((block, index) => (
                        <div key={block.id}>
                            {index > 0 && (
                                <AddBlockButton
                                    subtle
                                    onAdd={(type) => insertAt(index, type)}
                                />
                            )}
                            <VisualBlock
                                block={block}
                                previews={previews}
                                onChange={(data) => updateBlock(block.id, data)}
                                onDuplicate={() => duplicateBlock(block.id)}
                                onDelete={() => deleteBlock(block.id)}
                            />
                        </div>
                    ))}
                </SortableContext>
            </DndContext>

            <div className="mt-6">
                <AddBlockButton
                    onAdd={(type) => insertAt(blocks.length, type)}
                />
            </div>
        </div>
    );
}
