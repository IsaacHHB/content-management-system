import { Head, usePage } from '@inertiajs/react';

export type Seo = {
    title?: string | null;
    description?: string | null;
    image?: string | null;
};

export function SeoHead({ seo }: { seo?: Seo }) {
    const settings = usePage().props.settings as
        Record<string, string | null> | undefined;
    const site = settings?.site_name ?? 'Native Dads Network';
    const title = seo?.title ? `${seo.title} — ${site}` : site;

    return (
        <Head title={title}>
            {seo?.description && (
                <meta name="description" content={seo.description} />
            )}
            <meta property="og:title" content={seo?.title ?? site} />
            {seo?.description && (
                <meta property="og:description" content={seo.description} />
            )}
            {seo?.image && <meta property="og:image" content={seo.image} />}
            <meta name="twitter:card" content="summary_large_image" />
        </Head>
    );
}
