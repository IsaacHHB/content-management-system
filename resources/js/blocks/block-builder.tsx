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
import {
    ChevronDown,
    ChevronRight,
    Copy,
    GripVertical,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import { BLOCK_ORDER, BLOCKS } from '@/blocks/registry';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { Block, BlockType } from '@/types/models';

function newId(): string {
    return 'b' + crypto.randomUUID().replace(/-/g, '').slice(0, 20);
}

function SortableBlock({
    block,
    onChange,
    onDuplicate,
    onDelete,
}: {
    block: Block;
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
    const [open, setOpen] = useState(true);
    const def = BLOCKS[block.type];

    if (!def) {
        return null;
    }

    const Editor = def.Editor;
    const Icon = def.icon;

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'rounded-lg border bg-card',
                isDragging && 'opacity-60 shadow-lg',
            )}
        >
            <div className="flex items-center gap-2 border-b px-2 py-2">
                <button
                    type="button"
                    className="cursor-grab text-muted-foreground"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="size-4" />
                </button>
                <button
                    type="button"
                    onClick={() => setOpen((o) => !o)}
                    className="flex flex-1 items-center gap-2 text-sm font-medium"
                >
                    {open ? (
                        <ChevronDown className="size-4" />
                    ) : (
                        <ChevronRight className="size-4" />
                    )}
                    <Icon className="size-4" />
                    {def.label}
                </button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={onDuplicate}
                    title="Duplicate"
                >
                    <Copy className="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={onDelete}
                    title="Delete"
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>
            {open && (
                <div className="p-3">
                    <Editor
                        data={block.data}
                        onChange={(data) => onChange(data)}
                    />
                </div>
            )}
        </div>
    );
}

export function BlockBuilder({
    value,
    onChange,
}: {
    value: Block[];
    onChange: (blocks: Block[]) => void;
}) {
    const blocks = value ?? [];
    const sensors = useSensors(
        useSensor(PointerSensor),
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

    const addBlock = (type: BlockType) => {
        onChange([
            ...blocks,
            {
                id: newId(),
                type,
                data: structuredClone(BLOCKS[type].defaultData),
            },
        ]);
    };

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
        <div className="space-y-3">
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={onDragEnd}
            >
                <SortableContext
                    items={blocks.map((b) => b.id)}
                    strategy={verticalListSortingStrategy}
                >
                    <div className="space-y-3">
                        {blocks.map((block) => (
                            <SortableBlock
                                key={block.id}
                                block={block}
                                onChange={(data) => updateBlock(block.id, data)}
                                onDuplicate={() => duplicateBlock(block.id)}
                                onDelete={() => deleteBlock(block.id)}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>

            {blocks.length === 0 && (
                <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                    No blocks yet.
                </p>
            )}

            <Popover>
                <PopoverTrigger asChild>
                    <Button type="button" variant="outline" className="w-full">
                        <Plus className="size-4" /> Add block
                    </Button>
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
                                    onClick={() => addBlock(type)}
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
        </div>
    );
}
