import { BlockRenderer } from '@/blocks/block-renderer';
import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/format';
import type { Block } from '@/types/models';

type EventShowProps = {
    event: {
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
        address: string | null;
        city: string | null;
        state: string | null;
        zip: string | null;
        is_virtual: boolean;
        virtual_url: string | null;
        registration_url: string | null;
        description: Block[];
    };
    seo: Seo;
};

export default function EventShow({ event, seo }: EventShowProps) {
    const when = event.all_day
        ? formatDate(event.start_date)
        : formatDateTime(event.starts_at);
    const addressLine = [
        event.address,
        event.city,
        [event.state, event.zip].filter(Boolean).join(' '),
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    {event.title}
                </h1>

                <div className="mt-6 grid gap-3 rounded-lg border p-5 sm:grid-cols-2">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase">
                            When
                        </p>
                        <p className="mt-1">{when}</p>
                        {event.timezone && (
                            <p className="text-sm text-neutral-500">
                                {event.timezone}
                            </p>
                        )}
                    </div>
                    <div>
                        <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase">
                            Where
                        </p>
                        {event.is_virtual ? (
                            <p className="mt-1">
                                Virtual
                                {event.virtual_url && (
                                    <>
                                        {' — '}
                                        <a
                                            href={event.virtual_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="hover:underline"
                                        >
                                            Join link
                                        </a>
                                    </>
                                )}
                            </p>
                        ) : (
                            <div className="mt-1">
                                {event.location_name && (
                                    <p>{event.location_name}</p>
                                )}
                                {addressLine && (
                                    <p className="text-sm text-neutral-600">
                                        {addressLine}
                                    </p>
                                )}
                                {!event.location_name && !addressLine && (
                                    <p className="text-neutral-500">TBD</p>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {event.registration_url && (
                    <div className="mt-6">
                        <Button asChild size="lg">
                            <a
                                href={event.registration_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Register
                            </a>
                        </Button>
                    </div>
                )}

                <div className="mt-10">
                    <BlockRenderer blocks={event.description} />
                </div>
            </div>
        </>
    );
}
