import { Head, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';

import { PageHeader } from '@/components/cms/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { Menu, MenuItem } from '@/types/models';

type LinkableKey = 'Page' | 'Program' | 'Post' | 'Event';

type Linkable = { id: number; label: string };

type Linkables = Record<LinkableKey, Linkable[]>;

type LinkableTypes = Record<LinkableKey, string>;

type ItemForm = {
    label: string;
    linkable_type: string | null;
    linkable_id: number | null;
    custom_url: string | null;
    opens_new_tab: boolean;
    // Non-recursive (menus nest one level) so Inertia's form-key type stays finite.

    children: any[];
};

type MenuFormData = {
    name: string;
    items: ItemForm[];
};

function normalizeItem(item: MenuItem): ItemForm {
    return {
        label: item.label,
        linkable_type: item.linkable_type,
        linkable_id: item.linkable_id,
        custom_url: item.custom_url,
        opens_new_tab: item.opens_new_tab,
        children: (item.children ?? []).map(normalizeItem),
    };
}

function emptyItem(): ItemForm {
    return {
        label: '',
        linkable_type: null,
        linkable_id: null,
        custom_url: '',
        opens_new_tab: false,
        children: [],
    };
}

function targetKind(
    item: ItemForm,
    linkableTypes: LinkableTypes,
): LinkableKey | 'custom' {
    const key = (Object.keys(linkableTypes) as LinkableKey[]).find(
        (k) => linkableTypes[k] === item.linkable_type,
    );

    return key ?? 'custom';
}

function moveInArray<T>(list: T[], index: number, direction: -1 | 1): T[] {
    const target = index + direction;

    if (target < 0 || target >= list.length) {
        return list;
    }

    const next = [...list];
    [next[index], next[target]] = [next[target], next[index]];

    return next;
}

function ItemFields({
    item,
    linkables,
    linkableTypes,
    onChange,
}: {
    item: ItemForm;
    linkables: Linkables;
    linkableTypes: LinkableTypes;
    onChange: (patch: Partial<ItemForm>) => void;
}) {
    const kind = targetKind(item, linkableTypes);

    const onKindChange = (value: string) => {
        if (value === 'custom') {
            onChange({
                linkable_type: null,
                linkable_id: null,
                custom_url: '',
            });
        } else {
            onChange({
                linkable_type: linkableTypes[value as LinkableKey],
                linkable_id: null,
                custom_url: null,
            });
        }
    };

    return (
        <div className="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
            <div className="space-y-1.5">
                <Label>Label</Label>
                <Input
                    value={item.label}
                    onChange={(e) => onChange({ label: e.target.value })}
                />
            </div>

            <div className="space-y-1.5">
                <Label>Target</Label>
                <Select value={kind} onValueChange={onKindChange}>
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="custom">Custom URL</SelectItem>
                        {(Object.keys(linkableTypes) as LinkableKey[]).map(
                            (key) => (
                                <SelectItem key={key} value={key}>
                                    {key}
                                </SelectItem>
                            ),
                        )}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-1.5">
                {kind === 'custom' ? (
                    <>
                        <Label>URL</Label>
                        <Input
                            value={item.custom_url ?? ''}
                            onChange={(e) =>
                                onChange({ custom_url: e.target.value })
                            }
                            placeholder="/path or https://…"
                        />
                    </>
                ) : (
                    <>
                        <Label>Page</Label>
                        <Select
                            value={
                                item.linkable_id ? String(item.linkable_id) : ''
                            }
                            onValueChange={(value) =>
                                onChange({ linkable_id: Number(value) })
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select…" />
                            </SelectTrigger>
                            <SelectContent>
                                {linkables[kind].map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </>
                )}
            </div>

            <div className="flex items-center gap-2 pb-1.5">
                <Switch
                    checked={item.opens_new_tab}
                    onCheckedChange={(checked) =>
                        onChange({ opens_new_tab: checked })
                    }
                />
                <Label className="font-normal">New tab</Label>
            </div>
        </div>
    );
}

function MenuEditor({
    menu,
    linkables,
    linkableTypes,
}: {
    menu: Menu;
    linkables: Linkables;
    linkableTypes: LinkableTypes;
}) {
    const isHeader = menu.slot === 'header';
    const form = useForm<MenuFormData>({
        name: menu.name,
        items: (menu.items ?? []).map(normalizeItem),
    });

    const setItems = (items: ItemForm[]) => form.setData('items', items);

    const updateItem = (index: number, patch: Partial<ItemForm>) => {
        setItems(
            form.data.items.map((it, i) =>
                i === index ? { ...it, ...patch } : it,
            ),
        );
    };

    const updateChild = (
        parentIndex: number,
        childIndex: number,
        patch: Partial<ItemForm>,
    ) => {
        setItems(
            form.data.items.map((it, i) =>
                i === parentIndex
                    ? {
                          ...it,
                          children: it.children.map((c, ci) =>
                              ci === childIndex ? { ...c, ...patch } : c,
                          ),
                      }
                    : it,
            ),
        );
    };

    const addItem = () => setItems([...form.data.items, emptyItem()]);
    const removeItem = (index: number) =>
        setItems(form.data.items.filter((_, i) => i !== index));
    const moveItemAt = (index: number, direction: -1 | 1) =>
        setItems(moveInArray(form.data.items, index, direction));

    const addChild = (parentIndex: number) =>
        setItems(
            form.data.items.map((it, i) =>
                i === parentIndex
                    ? { ...it, children: [...it.children, emptyItem()] }
                    : it,
            ),
        );
    const removeChild = (parentIndex: number, childIndex: number) =>
        setItems(
            form.data.items.map((it, i) =>
                i === parentIndex
                    ? {
                          ...it,
                          children: it.children.filter(
                              (_, ci) => ci !== childIndex,
                          ),
                      }
                    : it,
            ),
        );
    const moveChildAt = (
        parentIndex: number,
        childIndex: number,
        direction: -1 | 1,
    ) =>
        setItems(
            form.data.items.map((it, i) =>
                i === parentIndex
                    ? {
                          ...it,
                          children: moveInArray(
                              it.children,
                              childIndex,
                              direction,
                          ),
                      }
                    : it,
            ),
        );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/admin/menus/${menu.id}`, { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-4 rounded-lg border bg-card p-4"
        >
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="max-w-sm flex-1 space-y-1.5">
                    <Label>Menu name</Label>
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </div>
                <Badge variant="outline" className="capitalize">
                    {menu.slot}
                </Badge>
            </div>

            <div className="space-y-3">
                {form.data.items.map((item, index) => (
                    <div
                        key={index}
                        className="space-y-3 rounded-md border p-3"
                    >
                        <ItemFields
                            item={item}
                            linkables={linkables}
                            linkableTypes={linkableTypes}
                            onChange={(patch) => updateItem(index, patch)}
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => moveItemAt(index, -1)}
                                disabled={index === 0}
                            >
                                <ArrowUp className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => moveItemAt(index, 1)}
                                disabled={index === form.data.items.length - 1}
                            >
                                <ArrowDown className="size-4" />
                            </Button>
                            {isHeader && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => addChild(index)}
                                >
                                    <Plus className="size-4" /> Add child
                                </Button>
                            )}
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => removeItem(index)}
                            >
                                <Trash2 className="size-4" /> Remove
                            </Button>
                        </div>

                        {isHeader && item.children.length > 0 && (
                            <div className="space-y-3 border-l pl-4">
                                {item.children.map((child, childIndex) => (
                                    <div
                                        key={childIndex}
                                        className="space-y-3 rounded-md border p-3"
                                    >
                                        <ItemFields
                                            item={child}
                                            linkables={linkables}
                                            linkableTypes={linkableTypes}
                                            onChange={(patch) =>
                                                updateChild(
                                                    index,
                                                    childIndex,
                                                    patch,
                                                )
                                            }
                                        />
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    moveChildAt(
                                                        index,
                                                        childIndex,
                                                        -1,
                                                    )
                                                }
                                                disabled={childIndex === 0}
                                            >
                                                <ArrowUp className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    moveChildAt(
                                                        index,
                                                        childIndex,
                                                        1,
                                                    )
                                                }
                                                disabled={
                                                    childIndex ===
                                                    item.children.length - 1
                                                }
                                            >
                                                <ArrowDown className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    removeChild(
                                                        index,
                                                        childIndex,
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />{' '}
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}
                {form.data.items.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No items yet.
                    </p>
                )}
            </div>

            <div className="flex items-center justify-between gap-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addItem}
                >
                    <Plus className="size-4" /> Add item
                </Button>
                <Button type="submit" disabled={form.processing}>
                    Save
                </Button>
            </div>
        </form>
    );
}

export default function MenusIndex({
    menus,
    linkables,
    linkableTypes,
}: {
    menus: Menu[];
    linkables: Linkables;
    linkableTypes: LinkableTypes;
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Menus" />
            <PageHeader
                title="Menus"
                description="Manage the header and footer navigation."
            />

            {menus.map((menu) => (
                <MenuEditor
                    key={menu.id}
                    menu={menu}
                    linkables={linkables}
                    linkableTypes={linkableTypes}
                />
            ))}
        </div>
    );
}
