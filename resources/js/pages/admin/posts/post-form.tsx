import { Head, Link, useForm } from '@inertiajs/react';

import { BlockEditor } from '@/blocks/block-editor';
import { CategoryManager } from '@/components/cms/category-manager';
import {
    Card,
    FormRow,
    PublishPanel,
    SeoPanel,
} from '@/components/cms/form-panels';
import type { FormLike } from '@/components/cms/form-panels';
import { PageHeader } from '@/components/cms/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import { toDatetimeLocal } from '@/lib/format';
import type { Block, Post } from '@/types/models';

type PostFormData = {
    title: string;
    slug: string;
    excerpt: string;
    blocks: Block[];
    status: string;
    published_at: string | null;
    seo_title: string;
    seo_description: string;
    og_media_asset_id: number | null;
    author_id: number | null;
    is_featured: boolean;
    category_ids: number[];
};

export default function PostForm({
    item,
    categories,
    authors,
}: {
    item?: Post;
    categories: { id: number; name: string }[];
    authors: { id: number; name: string }[];
}) {
    const isEdit = Boolean(item);
    const form = useForm<PostFormData>({
        title: item?.title ?? '',
        slug: item?.slug ?? '',
        excerpt: item?.excerpt ?? '',
        blocks: (item?.blocks ?? []) as Block[],
        status: item?.status ?? 'draft',
        published_at: toDatetimeLocal(item?.published_at),
        seo_title: item?.seo_title ?? '',
        seo_description: item?.seo_description ?? '',
        og_media_asset_id: item?.og_media_asset_id ?? null,
        author_id: item?.author_id ?? null,
        is_featured: item?.is_featured ?? false,
        category_ids: item?.categories?.map((c) => c.id) ?? [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/posts/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/posts');
        }
    };

    const toggleCategory = (id: number, checked: boolean) => {
        form.setData(
            'category_ids',
            checked
                ? [...form.data.category_ids, id]
                : form.data.category_ids.filter((c) => c !== id),
        );
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.title}` : 'New post'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit post' : 'New post'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/posts">Cancel</Link>
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
                                    value={form.data.title as string}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Slug"
                                error={form.errors.slug as string}
                                hint="Leave blank to auto-generate from the title."
                            >
                                <Input
                                    value={form.data.slug as string}
                                    onChange={(e) =>
                                        form.setData('slug', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Excerpt"
                                error={form.errors.excerpt as string}
                            >
                                <Textarea
                                    value={form.data.excerpt as string}
                                    onChange={(e) =>
                                        form.setData('excerpt', e.target.value)
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Content">
                            <BlockEditor
                                value={form.data.blocks as Block[]}
                                onChange={(b) => form.setData('blocks', b)}
                                title={form.data.title}
                            />
                        </Card>

                        <Card title="Details">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormRow
                                    label="Author"
                                    error={form.errors.author_id as string}
                                >
                                    <Select
                                        value={
                                            form.data.author_id != null
                                                ? String(form.data.author_id)
                                                : 'none'
                                        }
                                        onValueChange={(v) =>
                                            form.setData(
                                                'author_id',
                                                v === 'none' ? null : Number(v),
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                No author
                                            </SelectItem>
                                            {authors.map((author) => (
                                                <SelectItem
                                                    key={author.id}
                                                    value={String(author.id)}
                                                >
                                                    {author.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormRow>
                                <div className="flex items-center gap-2 pt-6">
                                    <Switch
                                        id="is_featured"
                                        checked={form.data.is_featured}
                                        onCheckedChange={(v) =>
                                            form.setData('is_featured', v)
                                        }
                                    />
                                    <Label htmlFor="is_featured">
                                        Featured
                                    </Label>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <PublishPanel form={form as unknown as FormLike} />
                        <SeoPanel
                            form={form as unknown as FormLike}
                            ogAsset={item?.og_media_asset ?? null}
                        />

                        <Card title="Categories">
                            <div className="space-y-2">
                                {categories.map((category) => (
                                    <div
                                        key={category.id}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`category-${category.id}`}
                                            checked={form.data.category_ids.includes(
                                                category.id,
                                            )}
                                            onCheckedChange={(v) =>
                                                toggleCategory(
                                                    category.id,
                                                    v === true,
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`category-${category.id}`}
                                        >
                                            {category.name}
                                        </Label>
                                    </div>
                                ))}
                                {categories.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        No categories yet.
                                    </p>
                                )}
                            </div>

                            <div className="border-t pt-3">
                                <CategoryManager categories={categories} />
                            </div>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    );
}
