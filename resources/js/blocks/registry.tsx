import {
    Columns3,
    Image as ImageIcon,
    LayoutTemplate,
    ListVideo,
    Megaphone,
    Minus,
    MoveVertical,
    Newspaper,
    PanelsTopLeft,
    Rows3,
    Type,
    Users,
    Calendar,
    ChevronsUpDown,
} from 'lucide-react';
import type { ComponentType } from 'react';

import { TiptapContent, TiptapEditor } from '@/blocks/tiptap';
import { MediaField } from '@/components/cms/media-picker';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
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
import { Textarea } from '@/components/ui/textarea';
import type { BlockType, MediaAsset, TiptapDoc } from '@/types/models';

type Data = Record<string, any>;
type EditorProps = { data: Data; onChange: (data: Data) => void };

export type BlockDef = {
    label: string;
    icon: ComponentType<{ className?: string }>;
    defaultData: Data;
    Editor: ComponentType<EditorProps>;
    Render: ComponentType<{ data: Data }>;
};

// --- small helpers ---

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
        </div>
    );
}

function LinkEditor({
    value,
    onChange,
}: {
    value: Data;
    onChange: (v: Data) => void;
}) {
    const link = value ?? { label: '', url: '' };

    return (
        <div className="grid grid-cols-2 gap-2">
            <Input
                placeholder="Button label"
                value={link.label ?? ''}
                onChange={(e) => onChange({ ...link, label: e.target.value })}
            />
            <Input
                placeholder="/path or https://…"
                value={link.url ?? ''}
                onChange={(e) => onChange({ ...link, url: e.target.value })}
            />
        </div>
    );
}

function set(data: Data, key: string, value: unknown) {
    return { ...data, [key]: value };
}

function mediaProps(data: Data, onChange: (d: Data) => void) {
    return {
        asset: (data.media as MediaAsset | undefined) ?? null,
        onChange: (asset: MediaAsset | null) =>
            onChange({
                ...data,
                media: asset,
                media_asset_id: asset?.id ?? null,
            }),
    };
}

const wrap = 'mx-auto max-w-4xl px-4';

// --- registry ---

export const BLOCKS: Record<BlockType, BlockDef> = {
    hero: {
        label: 'Hero',
        icon: LayoutTemplate,
        defaultData: {
            heading: '',
            sub: '',
            media_asset_id: null,
            cta: { label: '', url: '' },
        },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Field label="Heading">
                    <Input
                        value={data.heading ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'heading', e.target.value))
                        }
                    />
                </Field>
                <Field label="Subheading">
                    <Textarea
                        value={data.sub ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'sub', e.target.value))
                        }
                    />
                </Field>
                <MediaField
                    label="Background image"
                    {...mediaProps(data, onChange)}
                />
                <Field label="Call to action">
                    <LinkEditor
                        value={data.cta}
                        onChange={(v) => onChange(set(data, 'cta', v))}
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <section className="relative flex min-h-72 items-center justify-center bg-neutral-900 text-white">
                {data.media?.url && (
                    <img
                        src={data.media.url}
                        alt={data.media.alt_text ?? ''}
                        className="absolute inset-0 h-full w-full object-cover opacity-50"
                    />
                )}
                <div className="relative z-10 px-4 text-center">
                    <h1 className="text-4xl font-bold">{data.heading}</h1>
                    {data.sub && (
                        <p className="mx-auto mt-3 max-w-2xl text-lg">
                            {data.sub}
                        </p>
                    )}
                    {data.cta?.url && data.cta?.label && (
                        <a
                            href={data.cta.url}
                            className="mt-5 inline-block rounded-md bg-white px-5 py-2 font-medium text-neutral-900"
                        >
                            {data.cta.label}
                        </a>
                    )}
                </div>
            </section>
        ),
    },
    rich_text: {
        label: 'Rich text',
        icon: Type,
        defaultData: {
            content: {
                type: 'doc',
                content: [{ type: 'paragraph' }],
            } as TiptapDoc,
        },
        Editor: ({ data, onChange }) => (
            <TiptapEditor
                value={data.content}
                onChange={(doc) => onChange(set(data, 'content', doc))}
            />
        ),
        Render: ({ data }) => (
            <div className={wrap}>
                <TiptapContent
                    doc={data.content}
                    className="prose max-w-none"
                />
            </div>
        ),
    },
    image: {
        label: 'Image',
        icon: ImageIcon,
        defaultData: {
            media_asset_id: null,
            caption: '',
            alt: '',
            width: 'normal',
        },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <MediaField label="Image" {...mediaProps(data, onChange)} />
                <Field label="Alt text (required)">
                    <Input
                        value={data.alt ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'alt', e.target.value))
                        }
                    />
                </Field>
                <Field label="Caption">
                    <Input
                        value={data.caption ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'caption', e.target.value))
                        }
                    />
                </Field>
                <Field label="Width">
                    <Select
                        value={data.width ?? 'normal'}
                        onValueChange={(v) => onChange(set(data, 'width', v))}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="normal">Normal</SelectItem>
                            <SelectItem value="wide">Wide</SelectItem>
                            <SelectItem value="full">Full</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <figure
                className={
                    data.width === 'full'
                        ? ''
                        : data.width === 'wide'
                          ? 'mx-auto max-w-5xl px-4'
                          : wrap
                }
            >
                {data.media?.url && (
                    <img
                        src={data.media.url}
                        alt={data.alt ?? data.media.alt_text ?? ''}
                        className="w-full rounded-md"
                    />
                )}
                {data.caption && (
                    <figcaption className="mt-2 text-center text-sm text-neutral-500">
                        {data.caption}
                    </figcaption>
                )}
            </figure>
        ),
    },
    image_text: {
        label: 'Image + text',
        icon: PanelsTopLeft,
        defaultData: {
            media_asset_id: null,
            alt: '',
            image_position: 'left',
            content: { type: 'doc', content: [{ type: 'paragraph' }] },
        },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <MediaField label="Image" {...mediaProps(data, onChange)} />
                <Field label="Image position">
                    <Select
                        value={data.image_position ?? 'left'}
                        onValueChange={(v) =>
                            onChange(set(data, 'image_position', v))
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="left">Left</SelectItem>
                            <SelectItem value="right">Right</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
                <Field label="Text">
                    <TiptapEditor
                        value={data.content}
                        onChange={(doc) => onChange(set(data, 'content', doc))}
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <div className={`${wrap} grid items-center gap-6 md:grid-cols-2`}>
                <div
                    className={
                        data.image_position === 'right' ? 'md:order-2' : ''
                    }
                >
                    {data.media?.url && (
                        <img
                            src={data.media.url}
                            alt={data.alt ?? ''}
                            className="w-full rounded-md"
                        />
                    )}
                </div>
                <TiptapContent
                    doc={data.content}
                    className="prose max-w-none"
                />
            </div>
        ),
    },
    gallery_embed: {
        label: 'Gallery',
        icon: ImageIcon,
        defaultData: { gallery_id: null },
        Editor: ({ data, onChange }) => (
            <Field label="Gallery ID">
                <Input
                    type="number"
                    value={data.gallery_id ?? ''}
                    onChange={(e) =>
                        onChange(
                            set(
                                data,
                                'gallery_id',
                                e.target.value ? Number(e.target.value) : null,
                            ),
                        )
                    }
                />
            </Field>
        ),
        Render: ({ data }) => (
            <div className={`${wrap} grid grid-cols-2 gap-3 sm:grid-cols-3`}>
                {(data.gallery?.media_assets ?? []).map((a: MediaAsset) => (
                    <img
                        key={a.id}
                        src={a.thumb_url ?? a.url ?? ''}
                        alt={a.alt_text ?? ''}
                        className="aspect-square w-full rounded-md object-cover"
                    />
                ))}
            </div>
        ),
    },
    video_embed: {
        label: 'Video',
        icon: ListVideo,
        defaultData: { url: '', title: '' },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Field label="YouTube or Vimeo URL">
                    <Input
                        value={data.url ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'url', e.target.value))
                        }
                    />
                </Field>
                <Field label="Title">
                    <Input
                        value={data.title ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'title', e.target.value))
                        }
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => {
            const embed = toEmbedUrl(data.url ?? '');

            if (!embed) {
                return null;
            }

            return (
                <div className={wrap}>
                    <div className="aspect-video overflow-hidden rounded-md">
                        <iframe
                            src={embed}
                            title={data.title ?? 'Video'}
                            className="h-full w-full"
                            allowFullScreen
                        />
                    </div>
                </div>
            );
        },
    },
    cards: {
        label: 'Cards',
        icon: Columns3,
        defaultData: {
            columns: 3,
            cards: [{ title: '', text: '', link: { label: '', url: '' } }],
        },
        Editor: ({ data, onChange }) => {
            const cards: Data[] = data.cards ?? [];
            const update = (i: number, c: Data) =>
                onChange(
                    set(
                        data,
                        'cards',
                        cards.map((x, j) => (j === i ? c : x)),
                    ),
                );

            return (
                <div className="space-y-3">
                    <Field label="Columns">
                        <Select
                            value={String(data.columns ?? 3)}
                            onValueChange={(v) =>
                                onChange(set(data, 'columns', Number(v)))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {[2, 3, 4].map((n) => (
                                    <SelectItem key={n} value={String(n)}>
                                        {n}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    {cards.map((card, i) => (
                        <div
                            key={i}
                            className="space-y-2 rounded-md border p-3"
                        >
                            <Input
                                placeholder="Title"
                                value={card.title ?? ''}
                                onChange={(e) =>
                                    update(i, {
                                        ...card,
                                        title: e.target.value,
                                    })
                                }
                            />
                            <Textarea
                                placeholder="Text"
                                value={card.text ?? ''}
                                onChange={(e) =>
                                    update(i, { ...card, text: e.target.value })
                                }
                            />
                            <LinkEditor
                                value={card.link}
                                onChange={(v) =>
                                    update(i, { ...card, link: v })
                                }
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    onChange(
                                        set(
                                            data,
                                            'cards',
                                            cards.filter((_, j) => j !== i),
                                        ),
                                    )
                                }
                            >
                                Remove card
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            onChange(
                                set(data, 'cards', [
                                    ...cards,
                                    {
                                        title: '',
                                        text: '',
                                        link: { label: '', url: '' },
                                    },
                                ]),
                            )
                        }
                    >
                        Add card
                    </Button>
                </div>
            );
        },
        Render: ({ data }) => (
            <div
                className={`${wrap} grid gap-4`}
                style={{
                    gridTemplateColumns: `repeat(${Math.min(4, Math.max(2, data.columns ?? 3))}, minmax(0,1fr))`,
                }}
            >
                {(data.cards ?? []).map((card: Data, i: number) => (
                    <div key={i} className="rounded-md border p-4">
                        <h3 className="font-semibold">{card.title}</h3>
                        <p className="mt-1 text-sm text-neutral-600">
                            {card.text}
                        </p>
                        {card.link?.url && card.link?.label && (
                            <a
                                href={card.link.url}
                                className="mt-2 inline-block text-sm text-primary underline"
                            >
                                {card.link.label}
                            </a>
                        )}
                    </div>
                ))}
            </div>
        ),
    },
    cta_banner: {
        label: 'CTA banner',
        icon: Megaphone,
        defaultData: { heading: '', text: '', button: { label: '', url: '' } },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Input
                    placeholder="Heading"
                    value={data.heading ?? ''}
                    onChange={(e) =>
                        onChange(set(data, 'heading', e.target.value))
                    }
                />
                <Textarea
                    placeholder="Text"
                    value={data.text ?? ''}
                    onChange={(e) =>
                        onChange(set(data, 'text', e.target.value))
                    }
                />
                <LinkEditor
                    value={data.button}
                    onChange={(v) => onChange(set(data, 'button', v))}
                />
            </div>
        ),
        Render: ({ data }) => (
            <section className="my-6 bg-primary text-primary-foreground">
                <div className={`${wrap} py-10 text-center`}>
                    <h2 className="text-2xl font-bold">{data.heading}</h2>
                    {data.text && (
                        <p className="mx-auto mt-2 max-w-xl">{data.text}</p>
                    )}
                    {data.button?.url && data.button?.label && (
                        <a
                            href={data.button.url}
                            className="mt-4 inline-block rounded-md bg-white px-5 py-2 font-medium text-neutral-900"
                        >
                            {data.button.label}
                        </a>
                    )}
                </div>
            </section>
        ),
    },
    events_list: {
        label: 'Events list',
        icon: Calendar,
        defaultData: { count: 3, heading: 'Upcoming events' },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Input
                    placeholder="Heading"
                    value={data.heading ?? ''}
                    onChange={(e) =>
                        onChange(set(data, 'heading', e.target.value))
                    }
                />
                <Field label="Number of events">
                    <Input
                        type="number"
                        value={data.count ?? 3}
                        onChange={(e) =>
                            onChange(set(data, 'count', Number(e.target.value)))
                        }
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <div className={wrap}>
                {data.heading && (
                    <h2 className="mb-4 text-2xl font-bold">{data.heading}</h2>
                )}
                <ul className="space-y-2">
                    {(data.events ?? []).map((e: Data) => (
                        <li key={e.id} className="rounded-md border p-3">
                            <a
                                href={`/events/${e.slug}`}
                                className="font-medium"
                            >
                                {e.title}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    news_list: {
        label: 'News list',
        icon: Newspaper,
        defaultData: { count: 3, heading: 'Latest news' },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Input
                    placeholder="Heading"
                    value={data.heading ?? ''}
                    onChange={(e) =>
                        onChange(set(data, 'heading', e.target.value))
                    }
                />
                <Field label="Number of posts">
                    <Input
                        type="number"
                        value={data.count ?? 3}
                        onChange={(e) =>
                            onChange(set(data, 'count', Number(e.target.value)))
                        }
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <div className={wrap}>
                {data.heading && (
                    <h2 className="mb-4 text-2xl font-bold">{data.heading}</h2>
                )}
                <ul className="space-y-2">
                    {(data.posts ?? []).map((p: Data) => (
                        <li key={p.id} className="rounded-md border p-3">
                            <a href={`/news/${p.slug}`} className="font-medium">
                                {p.title}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    team_grid: {
        label: 'Team grid',
        icon: Users,
        defaultData: { heading: 'Our team', member_ids: [] },
        Editor: ({ data, onChange }) => (
            <div className="space-y-3">
                <Input
                    placeholder="Heading"
                    value={data.heading ?? ''}
                    onChange={(e) =>
                        onChange(set(data, 'heading', e.target.value))
                    }
                />
                <Field label="Member IDs (comma separated, blank = all active)">
                    <Input
                        value={(data.member_ids ?? []).join(', ')}
                        onChange={(e) =>
                            onChange(
                                set(
                                    data,
                                    'member_ids',
                                    e.target.value
                                        .split(',')
                                        .map((s) => Number(s.trim()))
                                        .filter(Boolean),
                                ),
                            )
                        }
                    />
                </Field>
            </div>
        ),
        Render: ({ data }) => (
            <div className={wrap}>
                {data.heading && (
                    <h2 className="mb-4 text-2xl font-bold">{data.heading}</h2>
                )}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    {(data.members ?? []).map((m: Data) => (
                        <div key={m.id} className="text-center">
                            {m.photo?.thumb_url && (
                                <img
                                    src={m.photo.thumb_url}
                                    alt={m.name}
                                    className="mx-auto size-24 rounded-full object-cover"
                                />
                            )}
                            <p className="mt-2 font-medium">{m.name}</p>
                            <p className="text-sm text-neutral-500">
                                {m.title}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        ),
    },
    accordion: {
        label: 'Accordion',
        icon: ChevronsUpDown,
        defaultData: {
            heading: '',
            items: [
                {
                    heading: '',
                    content: { type: 'doc', content: [{ type: 'paragraph' }] },
                },
            ],
        },
        Editor: ({ data, onChange }) => {
            const items: Data[] = data.items ?? [];
            const update = (i: number, it: Data) =>
                onChange(
                    set(
                        data,
                        'items',
                        items.map((x, j) => (j === i ? it : x)),
                    ),
                );

            return (
                <div className="space-y-3">
                    <Input
                        placeholder="Section heading"
                        value={data.heading ?? ''}
                        onChange={(e) =>
                            onChange(set(data, 'heading', e.target.value))
                        }
                    />
                    {items.map((item, i) => (
                        <div
                            key={i}
                            className="space-y-2 rounded-md border p-3"
                        >
                            <Input
                                placeholder="Question"
                                value={item.heading ?? ''}
                                onChange={(e) =>
                                    update(i, {
                                        ...item,
                                        heading: e.target.value,
                                    })
                                }
                            />
                            <TiptapEditor
                                value={item.content}
                                onChange={(doc) =>
                                    update(i, { ...item, content: doc })
                                }
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    onChange(
                                        set(
                                            data,
                                            'items',
                                            items.filter((_, j) => j !== i),
                                        ),
                                    )
                                }
                            >
                                Remove
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            onChange(
                                set(data, 'items', [
                                    ...items,
                                    {
                                        heading: '',
                                        content: {
                                            type: 'doc',
                                            content: [{ type: 'paragraph' }],
                                        },
                                    },
                                ]),
                            )
                        }
                    >
                        Add item
                    </Button>
                </div>
            );
        },
        Render: ({ data }) => (
            <div className={wrap}>
                {data.heading && (
                    <h2 className="mb-4 text-2xl font-bold">{data.heading}</h2>
                )}
                <Accordion type="single" collapsible>
                    {(data.items ?? []).map((item: Data, i: number) => (
                        <AccordionItem key={i} value={String(i)}>
                            <AccordionTrigger>{item.heading}</AccordionTrigger>
                            <AccordionContent>
                                <TiptapContent
                                    doc={item.content}
                                    className="prose prose-sm max-w-none"
                                />
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        ),
    },
    divider: {
        label: 'Divider',
        icon: Minus,
        defaultData: { style: 'line' },
        Editor: ({ data, onChange }) => (
            <Field label="Style">
                <Select
                    value={data.style ?? 'line'}
                    onValueChange={(v) => onChange(set(data, 'style', v))}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="line">Line</SelectItem>
                        <SelectItem value="dots">Dots</SelectItem>
                    </SelectContent>
                </Select>
            </Field>
        ),
        Render: () => (
            <div className={wrap}>
                <hr className="my-6" />
            </div>
        ),
    },
    spacer: {
        label: 'Spacer',
        icon: MoveVertical,
        defaultData: { size: 'medium' },
        Editor: ({ data, onChange }) => (
            <Field label="Size">
                <Select
                    value={data.size ?? 'medium'}
                    onValueChange={(v) => onChange(set(data, 'size', v))}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="small">Small</SelectItem>
                        <SelectItem value="medium">Medium</SelectItem>
                        <SelectItem value="large">Large</SelectItem>
                    </SelectContent>
                </Select>
            </Field>
        ),
        Render: ({ data }) => (
            <div
                style={{
                    height:
                        data.size === 'large'
                            ? 64
                            : data.size === 'small'
                              ? 16
                              : 32,
                }}
            />
        ),
    },
};

export const BLOCK_ORDER: BlockType[] = [
    'hero',
    'rich_text',
    'image',
    'image_text',
    'cards',
    'cta_banner',
    'accordion',
    'gallery_embed',
    'video_embed',
    'events_list',
    'news_list',
    'team_grid',
    'divider',
    'spacer',
];

export const IconRows3 = Rows3;

function toEmbedUrl(url: string): string | null {
    try {
        const u = new URL(url);
        const host = u.hostname.replace('www.', '');

        if (host === 'youtube.com' && u.searchParams.get('v')) {
            return `https://www.youtube.com/embed/${u.searchParams.get('v')}`;
        }

        if (host === 'youtu.be') {
            return `https://www.youtube.com/embed/${u.pathname.slice(1)}`;
        }

        if (host === 'vimeo.com') {
            return `https://player.vimeo.com/video/${u.pathname.slice(1)}`;
        }

        return null;
    } catch {
        return null;
    }
}
