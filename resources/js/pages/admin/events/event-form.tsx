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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { toDatetimeLocal } from '@/lib/format';
import type { Block, NdnEvent } from '@/types/models';

const TIMEZONES = [
    'America/Los_Angeles',
    'America/Denver',
    'America/Chicago',
    'America/New_York',
    'America/Phoenix',
    'America/Anchorage',
    'Pacific/Honolulu',
];

type EventFormData = {
    title: string;
    slug: string;
    description: Block[];
    status: string;
    published_at: string | null;
    seo_title: string;
    seo_description: string;
    og_media_asset_id: number | null;
    starts_at: string;
    ends_at: string;
    start_date: string;
    end_date: string;
    all_day: boolean;
    timezone: string;
    location_name: string;
    address: string;
    city: string;
    state: string;
    zip: string;
    is_virtual: boolean;
    virtual_url: string;
    registration_url: string;
};

export default function EventForm({ item }: { item?: NdnEvent }) {
    const isEdit = Boolean(item);
    const form = useForm<EventFormData>({
        title: item?.title ?? '',
        slug: item?.slug ?? '',
        description: (item?.description ?? []) as Block[],
        status: item?.status ?? 'draft',
        published_at: toDatetimeLocal(item?.published_at),
        seo_title: item?.seo_title ?? '',
        seo_description: item?.seo_description ?? '',
        og_media_asset_id: item?.og_media_asset_id ?? null,
        starts_at: toDatetimeLocal(item?.starts_at),
        ends_at: toDatetimeLocal(item?.ends_at),
        start_date: item?.start_date ?? '',
        end_date: item?.end_date ?? '',
        all_day: item?.all_day ?? false,
        timezone: item?.timezone ?? 'America/Los_Angeles',
        location_name: item?.location_name ?? '',
        address: item?.address ?? '',
        city: item?.city ?? '',
        state: item?.state ?? '',
        zip: item?.zip ?? '',
        is_virtual: item?.is_virtual ?? false,
        virtual_url: item?.virtual_url ?? '',
        registration_url: item?.registration_url ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/events/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/events');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.title}` : 'New event'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader title={isEdit ? 'Edit event' : 'New event'}>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/events">Cancel</Link>
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
                        </Card>

                        <Card title="Details">
                            <div className="flex items-center gap-2">
                                <Switch
                                    checked={form.data.all_day}
                                    onCheckedChange={(v) =>
                                        form.setData('all_day', v)
                                    }
                                    id="all_day"
                                />
                                <Label htmlFor="all_day">All day event</Label>
                            </div>

                            {form.data.all_day ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormRow
                                        label="Start date"
                                        error={form.errors.start_date as string}
                                    >
                                        <Input
                                            type="date"
                                            value={
                                                form.data.start_date as string
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'start_date',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="End date"
                                        error={form.errors.end_date as string}
                                    >
                                        <Input
                                            type="date"
                                            value={form.data.end_date as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'end_date',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                </div>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormRow
                                        label="Starts at"
                                        error={form.errors.starts_at as string}
                                    >
                                        <Input
                                            type="datetime-local"
                                            value={
                                                form.data.starts_at as string
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'starts_at',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="Ends at"
                                        error={form.errors.ends_at as string}
                                    >
                                        <Input
                                            type="datetime-local"
                                            value={form.data.ends_at as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'ends_at',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                </div>
                            )}

                            <FormRow
                                label="Timezone"
                                error={form.errors.timezone as string}
                            >
                                <Select
                                    value={form.data.timezone}
                                    onValueChange={(v) =>
                                        form.setData('timezone', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {TIMEZONES.map((tz) => (
                                            <SelectItem key={tz} value={tz}>
                                                {tz}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormRow>

                            <div className="flex items-center gap-2">
                                <Switch
                                    checked={form.data.is_virtual}
                                    onCheckedChange={(v) =>
                                        form.setData('is_virtual', v)
                                    }
                                    id="is_virtual"
                                />
                                <Label htmlFor="is_virtual">
                                    Virtual event
                                </Label>
                            </div>

                            {form.data.is_virtual ? (
                                <FormRow
                                    label="Virtual URL"
                                    error={form.errors.virtual_url as string}
                                >
                                    <Input
                                        value={form.data.virtual_url as string}
                                        onChange={(e) =>
                                            form.setData(
                                                'virtual_url',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormRow
                                        label="Location name"
                                        error={
                                            form.errors.location_name as string
                                        }
                                    >
                                        <Input
                                            value={
                                                form.data
                                                    .location_name as string
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'location_name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="Address"
                                        error={form.errors.address as string}
                                    >
                                        <Input
                                            value={form.data.address as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'address',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="City"
                                        error={form.errors.city as string}
                                    >
                                        <Input
                                            value={form.data.city as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'city',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="State"
                                        error={form.errors.state as string}
                                    >
                                        <Input
                                            value={form.data.state as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'state',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                    <FormRow
                                        label="Zip"
                                        error={form.errors.zip as string}
                                    >
                                        <Input
                                            value={form.data.zip as string}
                                            onChange={(e) =>
                                                form.setData(
                                                    'zip',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </FormRow>
                                </div>
                            )}

                            <FormRow
                                label="Registration URL"
                                error={form.errors.registration_url as string}
                            >
                                <Input
                                    value={form.data.registration_url as string}
                                    onChange={(e) =>
                                        form.setData(
                                            'registration_url',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Description">
                            <BlockEditor
                                value={form.data.description as Block[]}
                                onChange={(b) => form.setData('description', b)}
                                title={form.data.title}
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
