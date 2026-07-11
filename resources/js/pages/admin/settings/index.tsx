import { Head, useForm } from '@inertiajs/react';

import { Card, FormRow } from '@/components/cms/form-panels';
import type { FormLike } from '@/components/cms/form-panels';
import { MediaField } from '@/components/cms/media-picker';
import { PageHeader } from '@/components/cms/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { MediaAsset } from '@/types/models';

type SettingRow = { key: string; value: unknown; group: string };
type FieldType = 'text' | 'email' | 'url' | 'textarea' | 'media';

const FIELDS: Record<string, { label: string; type: FieldType }> = {
    site_name: { label: 'Site name', type: 'text' },
    tagline: { label: 'Tagline', type: 'text' },
    logo: { label: 'Logo', type: 'media' },
    partner_banner: { label: 'Partner banner', type: 'media' },
    footer_text: { label: 'Footer text', type: 'textarea' },
    contact_email: { label: 'Contact email', type: 'email' },
    contact_phone: { label: 'Contact phone', type: 'text' },
    mailing_address: { label: 'Mailing address', type: 'textarea' },
    facebook_url: { label: 'Facebook URL', type: 'url' },
    instagram_url: { label: 'Instagram URL', type: 'url' },
    youtube_url: { label: 'YouTube URL', type: 'url' },
    google_analytics_id: { label: 'Google Analytics ID', type: 'text' },
};

const GROUPS: { key: string; label: string }[] = [
    { key: 'general', label: 'General' },
    { key: 'contact', label: 'Contact' },
    { key: 'social', label: 'Social' },
    { key: 'seo', label: 'SEO' },
];

export default function SettingsIndex({
    settings,
    settingsMediaKeys,
    mediaAssets,
}: {
    settings: SettingRow[];
    settingsMediaKeys: string[];
    mediaAssets: MediaAsset[];
}) {
    const mediaKey = (key: string) => settingsMediaKeys.includes(key);
    const initial: Record<string, unknown> = {};

    for (const row of settings) {
        initial[row.key] = mediaKey(row.key)
            ? typeof row.value === 'object' && row.value
                ? ((row.value as { media_asset_id?: number }).media_asset_id ??
                  null)
                : (row.value ?? null)
            : (row.value ?? '');
    }

    const form = useForm<Record<string, any>>(initial);
    const assetFor = (key: string) =>
        mediaAssets.find((a) => a.id === form.data[key]) ?? null;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ settings: data }));
        form.put('/admin/settings', { preserveScroll: true });
    };

    const renderField = (key: string) => {
        const field = FIELDS[key];

        if (!field) {
            return null;
        }

        if (field.type === 'media') {
            return (
                <div key={key} className="space-y-1.5">
                    <MediaField
                        label={field.label}
                        asset={assetFor(key)}
                        onChange={(a) => form.setData(key, a?.id ?? null)}
                    />
                </div>
            );
        }

        return (
            <FormRow
                key={key}
                label={field.label}
                error={form.errors[key] as string}
            >
                {field.type === 'textarea' ? (
                    <Textarea
                        value={(form.data[key] as string) ?? ''}
                        onChange={(e) => form.setData(key, e.target.value)}
                    />
                ) : (
                    <Input
                        type={field.type}
                        value={(form.data[key] as string) ?? ''}
                        onChange={(e) => form.setData(key, e.target.value)}
                    />
                )}
            </FormRow>
        );
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="Settings" />
            <form onSubmit={submit} className="space-y-4">
                <PageHeader
                    title="Site settings"
                    description="Global configuration for the public site."
                >
                    <Button type="submit" disabled={form.processing}>
                        Save settings
                    </Button>
                </PageHeader>

                <Tabs defaultValue="general">
                    <TabsList>
                        {GROUPS.map((g) => (
                            <TabsTrigger key={g.key} value={g.key}>
                                {g.label}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                    {GROUPS.map((g) => (
                        <TabsContent key={g.key} value={g.key}>
                            <Card>
                                {settings
                                    .filter((s) => s.group === g.key)
                                    .map((s) => renderField(s.key))}
                            </Card>
                        </TabsContent>
                    ))}
                </Tabs>
            </form>
        </div>
    );
}

// Keep FormLike referenced for consistent typing across form screens.
export type { FormLike };
