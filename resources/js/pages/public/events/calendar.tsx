import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { Button } from '@/components/ui/button';

type CalendarEvent = {
    id: number;
    title: string;
    slug: string;
    date: string;
    time: string | null;
    all_day: boolean;
    is_virtual: boolean;
    location_name: string | null;
};

type CalendarProps = {
    month: string;
    label: string;
    prev: string;
    next: string;
    today: string;
    gridStart: string;
    gridEnd: string;
    events: CalendarEvent[];
    seo: Seo;
};

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/** Inclusive list of YYYY-MM-DD date strings, computed without timezone drift. */
function dateRange(start: string, end: string): string[] {
    const [ys, ms, ds] = start.split('-').map(Number);
    const [ye, me, de] = end.split('-').map(Number);
    const cursor = new Date(ys, ms - 1, ds);
    const last = new Date(ye, me - 1, de);
    const out: string[] = [];

    while (cursor <= last) {
        const y = cursor.getFullYear();
        const m = String(cursor.getMonth() + 1).padStart(2, '0');
        const d = String(cursor.getDate()).padStart(2, '0');
        out.push(`${y}-${m}-${d}`);
        cursor.setDate(cursor.getDate() + 1);
    }

    return out;
}

export default function EventsCalendar({
    month,
    label,
    prev,
    next,
    today,
    gridStart,
    gridEnd,
    events,
    seo,
}: CalendarProps) {
    const days = dateRange(gridStart, gridEnd);
    const weeks: string[][] = [];

    for (let i = 0; i < days.length; i += 7) {
        weeks.push(days.slice(i, i + 7));
    }

    const byDate = new Map<string, CalendarEvent[]>();

    for (const event of events) {
        const bucket = byDate.get(event.date) ?? [];
        bucket.push(event);
        byDate.set(event.date, bucket);
    }

    const monthNumber = Number(month.split('-')[1]);
    const go = (target: string) =>
        router.get(
            '/events/calendar',
            { month: target },
            { preserveScroll: true, preserveState: true },
        );

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-3xl font-bold tracking-tight">
                        Events Calendar
                    </h1>
                    <Link
                        href="/events"
                        className="text-sm font-medium underline"
                    >
                        List view
                    </Link>
                </div>

                <div className="mt-8 flex items-center justify-between">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => go(prev)}
                        aria-label="Previous month"
                    >
                        <ChevronLeft className="size-4" /> Prev
                    </Button>
                    <h2 className="text-xl font-semibold">{label}</h2>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => go(next)}
                        aria-label="Next month"
                    >
                        Next <ChevronRight className="size-4" />
                    </Button>
                </div>

                <div className="mt-6 overflow-x-auto">
                    <div className="min-w-[720px]">
                        <div className="grid grid-cols-7 border-b text-center text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                            {WEEKDAYS.map((day) => (
                                <div key={day} className="py-2">
                                    {day}
                                </div>
                            ))}
                        </div>
                        <div className="grid grid-cols-7">
                            {weeks.flat().map((date) => {
                                const inMonth =
                                    Number(date.split('-')[1]) === monthNumber;
                                const dayNumber = Number(date.split('-')[2]);
                                const dayEvents = byDate.get(date) ?? [];
                                const isToday = date === today;

                                return (
                                    <div
                                        key={date}
                                        className={`min-h-28 border-r border-b p-1.5 ${
                                            inMonth ? '' : 'bg-neutral-50'
                                        }`}
                                    >
                                        <div
                                            className={`mb-1 flex h-6 w-6 items-center justify-center rounded-full text-xs ${
                                                isToday
                                                    ? 'bg-neutral-900 font-semibold text-white'
                                                    : inMonth
                                                      ? 'text-neutral-700'
                                                      : 'text-neutral-400'
                                            }`}
                                        >
                                            {dayNumber}
                                        </div>
                                        <ul className="space-y-1">
                                            {dayEvents.map((event) => (
                                                <li key={event.id}>
                                                    <Link
                                                        href={`/events/${event.slug}`}
                                                        className="block truncate rounded bg-neutral-100 px-1.5 py-1 text-xs hover:bg-neutral-200"
                                                        title={event.title}
                                                    >
                                                        {event.time && (
                                                            <span className="font-medium">
                                                                {
                                                                    event.time
                                                                }{' '}
                                                            </span>
                                                        )}
                                                        {event.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {events.length === 0 && (
                    <p className="mt-6 text-sm text-neutral-500">
                        No events this month.
                    </p>
                )}
            </div>
        </>
    );
}
