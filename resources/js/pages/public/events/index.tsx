import { Link } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { formatDateOnly, formatDateTime } from '@/lib/format';

type EventSummary = {
    id: number;
    title: string;
    slug: string;
    starts_at: string | null;
    ends_at: string | null;
    start_date: string | null;
    end_date: string | null;
    all_day: boolean;
    timezone: string;
    location_name: string | null;
    is_virtual: boolean;
};

type EventsIndexProps = {
    upcoming: EventSummary[];
    past: EventSummary[];
    seo: Seo;
};

function EventList({
    events,
    emptyText,
}: {
    events: EventSummary[];
    emptyText: string;
}) {
    if (events.length === 0) {
        return <p className="mt-4 text-sm text-neutral-500">{emptyText}</p>;
    }

    return (
        <ul className="mt-6 divide-y">
            {events.map((event) => (
                <li key={event.id} className="py-4">
                    <Link
                        href={`/events/${event.slug}`}
                        className="font-semibold hover:underline"
                    >
                        {event.title}
                    </Link>
                    <p className="mt-1 text-sm text-neutral-600">
                        {event.all_day
                            ? formatDateOnly(event.start_date)
                            : formatDateTime(event.starts_at, event.timezone)}
                        {event.is_virtual
                            ? ' · Virtual'
                            : event.location_name
                              ? ` · ${event.location_name}`
                              : ''}
                    </p>
                </li>
            ))}
        </ul>
    );
}

export default function EventsIndex({ upcoming, past, seo }: EventsIndexProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-3xl font-bold tracking-tight">
                        Events
                    </h1>
                    <Link
                        href="/events/calendar"
                        className="text-sm font-medium underline"
                    >
                        Calendar view
                    </Link>
                </div>

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">Upcoming</h2>
                    <EventList
                        events={upcoming}
                        emptyText="No upcoming events."
                    />
                </section>

                <section className="mt-12">
                    <h2 className="text-xl font-semibold">Past Events</h2>
                    <EventList events={past} emptyText="No past events." />
                </section>
            </div>
        </>
    );
}
