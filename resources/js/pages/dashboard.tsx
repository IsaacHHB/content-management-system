import { Head, Link } from '@inertiajs/react';
import { Calendar, FileText, Mail, Newspaper, Boxes } from 'lucide-react';

import { PageHeader } from '@/components/cms/page-header';

type Counts = {
    pages: number;
    programs: number;
    events: number;
    posts: number;
    unread_contacts: number;
};

const cards = [
    { key: 'pages', label: 'Pages', href: '/admin/pages', icon: FileText },
    {
        key: 'programs',
        label: 'Programs',
        href: '/admin/programs',
        icon: Boxes,
    },
    { key: 'events', label: 'Events', href: '/admin/events', icon: Calendar },
    {
        key: 'posts',
        label: 'News posts',
        href: '/admin/posts',
        icon: Newspaper,
    },
    {
        key: 'unread_contacts',
        label: 'Unread messages',
        href: '/admin/contacts',
        icon: Mail,
    },
] as const;

export default function Dashboard({ counts }: { counts: Counts }) {
    return (
        <div className="space-y-6 p-4">
            <Head title="Dashboard" />
            <PageHeader
                title="Dashboard"
                description="Native Dads Network content overview."
            />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {cards.map((card) => (
                    <Link
                        key={card.key}
                        href={card.href}
                        className="flex flex-col gap-2 rounded-xl border bg-card p-4 transition-colors hover:border-primary"
                    >
                        <card.icon className="size-5 text-muted-foreground" />
                        <span className="text-3xl font-semibold">
                            {counts[card.key] ?? 0}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {card.label}
                        </span>
                    </Link>
                ))}
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                    { label: 'New page', href: '/admin/pages/create' },
                    { label: 'New program', href: '/admin/programs/create' },
                    { label: 'New event', href: '/admin/events/create' },
                    { label: 'New post', href: '/admin/posts/create' },
                ].map((action) => (
                    <Link
                        key={action.href}
                        href={action.href}
                        className="rounded-lg border border-dashed p-4 text-center text-sm hover:bg-accent"
                    >
                        {action.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
