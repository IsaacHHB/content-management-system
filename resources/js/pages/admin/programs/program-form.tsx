import { Head, Link, useForm } from '@inertiajs/react';

import { BlockEditor } from '@/blocks/block-editor';
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
import { Textarea } from '@/components/ui/textarea';
import { toDatetimeLocal } from '@/lib/format';
import type { Block, Program } from '@/types/models';

type ProgramFormData = {
    title: string;
    slug: string;
    excerpt: string;
    blocks: Block[];
    status: string;
    published_at: string | null;
    seo_title: string;
    seo_description: string;
    og_media_asset_id: number | null;
    contact_name: string;
    contact_email: string;
    contact_phone: string;
    external_url: string;
};

export default function ProgramForm({ item }: { item?: Program }) {
    const isEdit = Boolean(item);
    const form = useForm<ProgramFormData>({
        title: item?.title ?? '',
        slug: item?.slug ?? '',
        excerpt: item?.excerpt ?? '',
        blocks: (item?.blocks ?? []) as Block[],
        status: item?.status ?? 'draft',
        published_at: toDatetimeLocal(item?.published_at),
        seo_title: item?.seo_title ?? '',
        seo_description: item?.seo_description ?? '',
        og_media_asset_id: item?.og_media_asset_id ?? null,
        contact_name: item?.contact_name ?? '',
        contact_email: item?.contact_email ?? '',
        contact_phone: item?.contact_phone ?? '',
        external_url: item?.external_url ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/programs/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/programs');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.title}` : 'New program'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit program' : 'New program'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/programs">Cancel</Link>
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

                        <Card title="Program details">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormRow
                                    label="Contact name"
                                    error={form.errors.contact_name as string}
                                >
                                    <Input
                                        value={form.data.contact_name as string}
                                        onChange={(e) =>
                                            form.setData(
                                                'contact_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="Contact email"
                                    error={form.errors.contact_email as string}
                                >
                                    <Input
                                        value={
                                            form.data.contact_email as string
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'contact_email',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="Contact phone"
                                    error={form.errors.contact_phone as string}
                                >
                                    <Input
                                        value={
                                            form.data.contact_phone as string
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'contact_phone',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="External URL"
                                    error={form.errors.external_url as string}
                                >
                                    <Input
                                        value={form.data.external_url as string}
                                        onChange={(e) =>
                                            form.setData(
                                                'external_url',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                            </div>
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
