import { Head, Link, useForm } from '@inertiajs/react';

import { Card, FormRow } from '@/components/cms/form-panels';
import { MediaField } from '@/components/cms/media-picker';
import { PageHeader } from '@/components/cms/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { Partner } from '@/types/models';

type PartnerFormData = {
    name: string;
    slug: string;
    website_url: string;
    logo_media_asset_id: number | null;
    is_active: boolean;
};

export default function PartnerForm({ item }: { item?: Partner }) {
    const isEdit = Boolean(item);
    const form = useForm<PartnerFormData>({
        name: item?.name ?? '',
        slug: item?.slug ?? '',
        website_url: item?.website_url ?? '',
        logo_media_asset_id: item?.logo_media_asset_id ?? null,
        is_active: item?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/partners/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/partners');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.name}` : 'New partner'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit partner' : 'New partner'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/partners">Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </PageHeader>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <FormRow label="Name" error={form.errors.name}>
                                <Input
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Slug"
                                error={form.errors.slug}
                                hint="Leave blank to auto-generate from the name."
                            >
                                <Input
                                    value={form.data.slug}
                                    onChange={(e) =>
                                        form.setData('slug', e.target.value)
                                    }
                                />
                            </FormRow>
                            <FormRow
                                label="Website URL"
                                error={form.errors.website_url}
                                hint="Optional. The logo links here on the public site."
                            >
                                <Input
                                    type="url"
                                    placeholder="https://example.org"
                                    value={form.data.website_url}
                                    onChange={(e) =>
                                        form.setData(
                                            'website_url',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Logo">
                            <MediaField
                                asset={item?.logo ?? null}
                                onChange={(asset) =>
                                    form.setData(
                                        'logo_media_asset_id',
                                        asset?.id ?? null,
                                    )
                                }
                            />
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Status">
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="is_active"
                                    checked={form.data.is_active}
                                    onCheckedChange={(v) =>
                                        form.setData('is_active', v)
                                    }
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Inactive partners are hidden from the public
                                site.
                            </p>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    );
}
