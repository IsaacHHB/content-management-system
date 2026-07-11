import { MediaField } from '@/components/cms/media-picker';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { MediaAsset } from '@/types/models';

// A minimal shape compatible with Inertia's useForm.
export type FormLike = {
    data: Record<string, any>;
    setData: (key: any, value?: any) => void;
    errors: Record<string, string>;
};

export function FormRow({
    label,
    error,
    children,
    hint,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
    hint?: string;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && <InputError message={error} />}
        </div>
    );
}

export function Card({
    title,
    children,
}: {
    title?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-4 rounded-lg border bg-card p-4">
            {title && <h2 className="text-sm font-semibold">{title}</h2>}
            {children}
        </div>
    );
}

export function PublishPanel({ form }: { form: FormLike }) {
    return (
        <Card title="Publishing">
            <FormRow label="Status" error={form.errors.status}>
                <Select
                    value={form.data.status}
                    onValueChange={(v) => form.setData('status', v)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="published">Published</SelectItem>
                        <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                </Select>
            </FormRow>
            <FormRow
                label="Publish date"
                error={form.errors.published_at}
                hint="Leave blank to publish immediately."
            >
                <Input
                    type="datetime-local"
                    value={form.data.published_at ?? ''}
                    onChange={(e) =>
                        form.setData('published_at', e.target.value || null)
                    }
                />
            </FormRow>
        </Card>
    );
}

export function SeoPanel({
    form,
    ogAsset,
}: {
    form: FormLike;
    ogAsset: MediaAsset | null;
}) {
    return (
        <Card title="SEO">
            <FormRow label="SEO title" error={form.errors.seo_title}>
                <Input
                    value={form.data.seo_title ?? ''}
                    onChange={(e) => form.setData('seo_title', e.target.value)}
                />
            </FormRow>
            <FormRow
                label="SEO description"
                error={form.errors.seo_description}
            >
                <Textarea
                    value={form.data.seo_description ?? ''}
                    onChange={(e) =>
                        form.setData('seo_description', e.target.value)
                    }
                />
            </FormRow>
            <MediaField
                label="Social share image"
                asset={ogAsset}
                onChange={(asset) =>
                    form.setData('og_media_asset_id', asset?.id ?? null)
                }
            />
        </Card>
    );
}
