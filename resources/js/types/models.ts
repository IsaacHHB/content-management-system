// Shared domain types mirroring the Eloquent models.

export type PublishStatus = 'draft' | 'published' | 'archived';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type AuditUser = { id: number; name: string } | null;

export type MediaConversions = {
    thumb?: string;
    medium?: string;
    large?: string;
};

export type MediaAsset = {
    id: number;
    uuid: string;
    type: 'image' | 'document';
    original_name: string;
    alt_text: string | null;
    caption: string | null;
    credit: string | null;
    focal_point: { x: number; y: number } | null;
    status: string | null;
    url?: string | null;
    thumb_url?: string | null;
    conversions?: MediaConversions;
    references_count?: number;
    created_at: string;
    updated_at: string;
};

// --- Block system ---

export type Block = {
    id: string;
    type: BlockType;

    data: Record<string, any>;
};

export type BlockType =
    | 'hero'
    | 'rich_text'
    | 'image'
    | 'image_text'
    | 'gallery_embed'
    | 'video_embed'
    | 'cards'
    | 'cta_banner'
    | 'events_list'
    | 'news_list'
    | 'team_grid'
    | 'partners'
    | 'accordion'
    | 'divider'
    | 'spacer';

export type TiptapDoc = { type: 'doc'; content?: unknown[] };

// --- Content models ---

type Timestamps = {
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
};
type Audited = {
    created_by?: number | null;
    updated_by?: number | null;
    updated_by_user?: AuditUser;
};
type Seo = {
    seo_title: string | null;
    seo_description: string | null;
    og_media_asset_id: number | null;
    og_media_asset?: MediaAsset | null;
};

export type Page = Timestamps &
    Audited &
    Seo & {
        id: number;
        parent_id: number | null;
        title: string;
        slug: string;
        path?: string;
        blocks: Block[];
        status: PublishStatus;
        published_at: string | null;
        locale: string;
        is_locked: boolean;
        sort_order: number;
        parent?: Pick<Page, 'id' | 'title' | 'slug'> | null;
    };

export type Program = Timestamps &
    Audited &
    Seo & {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        blocks: Block[];
        status: PublishStatus;
        published_at: string | null;
        contact_name: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        external_url: string | null;
        sort_order: number;
    };

export type NdnEvent = Timestamps &
    Audited &
    Seo & {
        id: number;
        title: string;
        slug: string;
        description: Block[];
        status: PublishStatus;
        published_at: string | null;
        starts_at: string | null;
        ends_at: string | null;
        start_date: string | null;
        end_date: string | null;
        all_day: boolean;
        timezone: string;
        location_name: string | null;
        address: string | null;
        city: string | null;
        state: string | null;
        zip: string | null;
        is_virtual: boolean;
        virtual_url: string | null;
        registration_url: string | null;
    };

export type Category = { id: number; name: string; slug: string };

export type Post = Timestamps &
    Audited &
    Seo & {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        blocks: Block[];
        status: PublishStatus;
        published_at: string | null;
        author_id: number | null;
        is_featured: boolean;
        categories?: Category[];
    };

export type Gallery = Timestamps &
    Audited & {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        status: PublishStatus;
        published_at: string | null;
        sort_order: number;
        media_assets?: (MediaAsset & {
            pivot: {
                alt_text: string;
                caption: string | null;
                sort_order: number;
            };
        })[];
    };

export type TeamMember = Timestamps &
    Audited & {
        id: number;
        name: string;
        slug: string;
        title: string;
        group: 'staff' | 'board';
        bio: string;
        email: string | null;
        show_email: boolean;
        phone: string | null;
        show_phone: boolean;
        photo_media_asset_id: number | null;
        photo?: MediaAsset | null;
        sort_order: number;
        is_active: boolean;
    };

export type Partner = Timestamps &
    Audited & {
        id: number;
        name: string;
        slug: string;
        website_url: string | null;
        logo_media_asset_id: number | null;
        logo?: MediaAsset | null;
        sort_order: number;
        is_active: boolean;
    };

export type MenuItem = {
    id: number;
    menu_id: number;
    parent_id: number | null;
    label: string;
    linkable_type: string | null;
    linkable_id: number | null;
    custom_url: string | null;
    opens_new_tab: boolean;
    sort_order: number;
    url?: string;
    children?: MenuItem[];
};

export type Menu = {
    id: number;
    name: string;
    slot: 'header' | 'footer';
    items?: MenuItem[];
};

export type ContactSubmission = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string | null;
    message: string;
    is_read: boolean;
    created_at: string;
};

export type Invite = {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    accepted_at: string | null;
    inviter?: AuditUser;
    created_at: string;
};

export type Activity = {
    id: number;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    causer?: AuditUser;
    properties: Record<string, unknown>;
    created_at: string;
};

export type SiteSettings = Record<string, string | number | null>;
