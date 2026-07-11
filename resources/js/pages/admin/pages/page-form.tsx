import { Head, Link, useForm } from '@inertiajs/react';

import { BlockBuilder } from '@/blocks/block-builder';
import {
    Card,
    FormRow,
    PublishPanel,
    SeoPanel,
} from '@/components/cms/form-panels';
import type { FormLike } from '@/components/cms/form-panels';
import { PageHeader } from '@/components/cms/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { toDatetimeLocal } from '@/lib/format';
import type { Block, Page } from '@/types/models';

type PageFormData = {
    parent_id: number | null;
    title: string;
    slug: string;
    locale: string;
    blocks: Block[];
    status: string;
    published_at: string | null;
    seo_title: string;
    seo_description: string;
    og_media_asset_id: number | null;
    is_locked: boolean;
    sort_order: number;
};

export default function PageForm({
    item,
    parentOptions,
}: {
    item?: Page;
    parentOptions: { id: number; title: string }[];
}) {
    const isEdit = Boolean(item);
    const locked = Boolean(item?.is_locked);
    const form = useForm<PageFormData>({
        parent_id: item?.parent_id ?? null,
        title: item?.title ?? '',
        slug: item?.slug ?? '',
        locale: item?.locale ?? 'en',
        blocks: (item?.blocks ?? []) as Block[],
        status: item?.status ?? 'draft',
        published_at: toDatetimeLocal(item?.published_at),
        seo_title: item?.seo_title ?? '',
        seo_description: item?.seo_description ?? '',
        og_media_asset_id: item?.og_media_asset_id ?? null,
        is_locked: item?.is_locked ?? false,
        sort_order: item?.sort_order ?? 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/pages/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/pages');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.title}` : 'New page'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit page' : 'New page'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/pages">Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </PageHeader>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <FormRow
                                label="Title"
                                error={form.errors.title as string}
                            >
                                <Input
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Slug"
                                error={form.errors.slug as string}
                                hint={
                                    locked
                                        ? 'Locked pages cannot be re-slugged.'
                                        : undefined
                                }
                            >
                                <Input
                                    value={form.data.slug}
                                    onChange={(e) =>
                                        form.setData('slug', e.target.value)
                                    }
                                    disabled={locked}
                                />
                            </FormRow>
                            <FormRow
                                label="Parent page"
                                error={form.errors.parent_id as string}
                            >
                                <Select
                                    value={
                                        form.data.parent_id === null
                                            ? 'none'
                                            : String(form.data.parent_id)
                                    }
                                    onValueChange={(v) =>
                                        form.setData(
                                            'parent_id',
                                            v === 'none' ? null : Number(v),
                                        )
                                    }
                                    disabled={locked}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            None (top level)
                                        </SelectItem>
                                        {parentOptions.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.title}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormRow>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormRow
                                    label="Locale"
                                    error={form.errors.locale as string}
                                >
                                    <Input
                                        value={form.data.locale}
                                        onChange={(e) =>
                                            form.setData(
                                                'locale',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="Sort order"
                                    error={form.errors.sort_order as string}
                                >
                                    <Input
                                        type="number"
                                        value={form.data.sort_order}
                                        onChange={(e) =>
                                            form.setData(
                                                'sort_order',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </FormRow>
                            </div>
                            <FormRow
                                label="Locked"
                                error={form.errors.is_locked as string}
                                hint="Locked pages cannot be moved or re-slugged."
                            >
                                <Switch
                                    checked={form.data.is_locked}
                                    onCheckedChange={(v) =>
                                        form.setData('is_locked', v)
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Content">
                            <BlockBuilder
                                value={form.data.blocks}
                                onChange={(b) => form.setData('blocks', b)}
                            />
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <PublishPanel form={form as unknown as FormLike} />
                        <SeoPanel
                            form={form as unknown as FormLike}
                            ogAsset={item?.og_media_asset ?? null}
                        />
                    </div>
                </div>
            </form>
        </div>
    );
}
