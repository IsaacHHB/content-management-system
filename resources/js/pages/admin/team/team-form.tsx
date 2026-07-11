import { Head, Link, useForm } from '@inertiajs/react';

import { Card, FormRow } from '@/components/cms/form-panels';
import { MediaField } from '@/components/cms/media-picker';
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
import { Textarea } from '@/components/ui/textarea';
import type { TeamMember } from '@/types/models';

type TeamMemberFormData = {
    name: string;
    slug: string;
    title: string;
    group: 'staff' | 'board';
    bio: string;
    email: string;
    show_email: boolean;
    phone: string;
    show_phone: boolean;
    photo_media_asset_id: number | null;
    is_active: boolean;
};

export default function TeamMemberForm({ item }: { item?: TeamMember }) {
    const isEdit = Boolean(item);
    const form = useForm<TeamMemberFormData>({
        name: item?.name ?? '',
        slug: item?.slug ?? '',
        title: item?.title ?? '',
        group: item?.group ?? 'staff',
        bio: item?.bio ?? '',
        email: item?.email ?? '',
        show_email: item?.show_email ?? false,
        phone: item?.phone ?? '',
        show_phone: item?.show_phone ?? false,
        photo_media_asset_id: item?.photo_media_asset_id ?? null,
        is_active: item?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            form.put(`/admin/team/${item!.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/team');
        }
    };

    return (
        <div className="p-4">
            <Head title={isEdit ? `Edit ${item!.name}` : 'New team member'} />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader
                    title={isEdit ? 'Edit team member' : 'New team member'}
                >
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/team">Cancel</Link>
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
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormRow
                                    label="Title"
                                    error={form.errors.title}
                                >
                                    <Input
                                        value={form.data.title}
                                        onChange={(e) =>
                                            form.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="Group"
                                    error={form.errors.group}
                                    hint="Staff and board are shown as separate tabs on the public team page."
                                >
                                    <Select
                                        value={form.data.group}
                                        onValueChange={(v) =>
                                            form.setData(
                                                'group',
                                                v as 'staff' | 'board',
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="staff">
                                                Staff
                                            </SelectItem>
                                            <SelectItem value="board">
                                                Board
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormRow>
                            </div>
                            <FormRow label="Bio" error={form.errors.bio}>
                                <Textarea
                                    value={form.data.bio}
                                    onChange={(e) =>
                                        form.setData('bio', e.target.value)
                                    }
                                />
                            </FormRow>
                        </Card>

                        <Card title="Photo">
                            <MediaField
                                asset={item?.photo ?? null}
                                onChange={(asset) =>
                                    form.setData(
                                        'photo_media_asset_id',
                                        asset?.id ?? null,
                                    )
                                }
                            />
                        </Card>

                        <Card title="Contact">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormRow
                                    label="Email"
                                    error={form.errors.email}
                                >
                                    <Input
                                        value={form.data.email}
                                        onChange={(e) =>
                                            form.setData(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                                <FormRow
                                    label="Phone"
                                    error={form.errors.phone}
                                >
                                    <Input
                                        value={form.data.phone}
                                        onChange={(e) =>
                                            form.setData(
                                                'phone',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </FormRow>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="show_email"
                                    checked={form.data.show_email}
                                    onCheckedChange={(v) =>
                                        form.setData('show_email', v)
                                    }
                                />
                                <Label htmlFor="show_email">
                                    Show email publicly
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="show_phone"
                                    checked={form.data.show_phone}
                                    onCheckedChange={(v) =>
                                        form.setData('show_phone', v)
                                    }
                                />
                                <Label htmlFor="show_phone">
                                    Show phone publicly
                                </Label>
                            </div>
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
                                Inactive members are hidden from the public
                                site.
                            </p>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    );
}
