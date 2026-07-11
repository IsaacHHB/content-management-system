import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { PartnersStrip } from '@/components/public/partners-strip';
import type { PublicPartner } from '@/components/public/partners-strip';

type PreviewMenuItem = { id: number; label: string; url: string | null };

type SiteChrome = {
    menus: { header: PreviewMenuItem[]; footer: PreviewMenuItem[] };
    partners: PublicPartner[];
};

/**
 * The real public layout shell (header nav, page title, content slot, partner
 * strip, footer) used by the admin visual editor / preview so an editor sees
 * the page in its true site context. Header/footer/partners come from the
 * shared `siteChrome` prop; the content area is passed as children.
 */
export function PublicShell({
    title,
    showTitle = true,
    children,
}: {
    title?: string;
    showTitle?: boolean;
    children: ReactNode;
}) {
    const page = usePage();
    const settings = (page.props.settings ?? {}) as Record<
        string,
        string | null
    >;
    const chrome = (page.props.siteChrome as SiteChrome | null) ?? {
        menus: { header: [], footer: [] },
        partners: [],
    };
    const siteName = settings.site_name || 'Native Dads Network';

    return (
        <div className="flex min-h-full flex-col bg-white text-neutral-900">
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                    <span className="text-lg font-bold">{siteName}</span>
                    <nav className="flex flex-wrap gap-4 text-sm">
                        {chrome.menus.header.map((item) => (
                            <span key={item.id}>{item.label}</span>
                        ))}
                    </nav>
                </div>
            </header>

            <main className="flex-1">
                <div className="mx-auto max-w-6xl px-4 py-12">
                    {showTitle && title && (
                        <h1 className="text-3xl font-bold tracking-tight">
                            {title}
                        </h1>
                    )}
                    <div className={showTitle && title ? 'mt-6' : ''}>
                        {children}
                    </div>
                </div>
            </main>

            <PartnersStrip partners={chrome.partners} />

            <footer className="border-t bg-neutral-50">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
                    <div>
                        <p className="font-semibold">{siteName}</p>
                        {settings.mailing_address && (
                            <p className="text-sm text-neutral-600">
                                {settings.mailing_address}
                            </p>
                        )}
                        {settings.contact_email && (
                            <p className="text-sm text-neutral-600">
                                {settings.contact_email}
                            </p>
                        )}
                    </div>
                    <nav className="flex flex-col gap-2 text-sm">
                        {chrome.menus.footer.map((item) => (
                            <span key={item.id}>{item.label}</span>
                        ))}
                    </nav>
                    <div className="text-sm text-neutral-500">
                        {settings.footer_text ?? `© ${siteName}`}
                    </div>
                </div>
            </footer>
        </div>
    );
}
