import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { PartnersStrip } from '@/components/public/partners-strip';
import type { PublicPartner } from '@/components/public/partners-strip';
import type { MenuItem } from '@/types/models';

type PublicShared = {
    settings: Record<string, string | null>;
    publicMenus?: { header: MenuItem[]; footer: MenuItem[] };
    publicPartners?: PublicPartner[];
};

export default function PublicLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const settings = (page.props.settings ?? {}) as PublicShared['settings'];
    const menus = (page.props.publicMenus as PublicShared['publicMenus']) ?? {
        header: [],
        footer: [],
    };
    const partners =
        (page.props.publicPartners as PublicShared['publicPartners']) ?? [];
    const siteName = settings.site_name ?? 'Native Dads Network';

    return (
        <div className="flex min-h-screen flex-col bg-white text-neutral-900">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:p-2"
            >
                Skip to content
            </a>
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                    <Link href="/" className="text-lg font-bold">
                        {siteName}
                    </Link>
                    <nav className="flex flex-wrap gap-4 text-sm">
                        {menus.header.map((item) => (
                            <a
                                key={item.id}
                                href={item.url ?? '#'}
                                target={
                                    item.opens_new_tab ? '_blank' : undefined
                                }
                                rel="noreferrer"
                            >
                                {item.label}
                            </a>
                        ))}
                    </nav>
                </div>
            </header>

            <main id="main" className="flex-1">
                {children}
            </main>

            <PartnersStrip partners={partners} />

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
                        {settings.contact_phone && (
                            <p className="text-sm text-neutral-600">
                                {settings.contact_phone}
                            </p>
                        )}
                    </div>
                    <nav className="flex flex-col gap-2 text-sm">
                        {menus.footer.map((item) => (
                            <a key={item.id} href={item.url ?? '#'}>
                                {item.label}
                            </a>
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
