import { Link, usePage } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { formatDate, formatDateTime } from '@/lib/format';
import type { TeamMember } from '@/types/models';

type HomeProgram = { id: number; title: string; slug: string; excerpt: string };

type HomeEvent = {
    id: number;
    title: string;
    slug: string;
    starts_at: string | null;
    start_date: string | null;
    all_day: boolean;
    location_name: string | null;
    is_virtual: boolean;
};

type HomePost = {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    published_at: string | null;
};

type HomeProps = {
    programs: HomeProgram[];
    events: HomeEvent[];
    posts: HomePost[];
    team: TeamMember[];
    seo: Seo;
};

export default function Home({
    programs,
    events,
    posts,
    team,
    seo,
}: HomeProps) {
    const settings = usePage().props.settings as
        Record<string, string | null> | undefined;
    const siteName = settings?.site_name ?? 'Native Dads Network';
    const tagline = settings?.tagline ?? '';

    return (
        <>
            <SeoHead seo={seo} />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-6xl px-4 py-16 text-center sm:py-24">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-5xl">
                        {siteName}
                    </h1>
                    {tagline && (
                        <p className="mx-auto mt-4 max-w-2xl text-lg text-neutral-600">
                            {tagline}
                        </p>
                    )}
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-12">
                <h2 className="text-2xl font-bold">Our Programs</h2>
                <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {programs.map((program) => (
                        <Link
                            key={program.id}
                            href={`/programs/${program.slug}`}
                            className="rounded-lg border p-5 transition hover:border-neutral-400 hover:shadow-sm"
                        >
                            <h3 className="font-semibold">{program.title}</h3>
                            <p className="mt-2 line-clamp-3 text-sm text-neutral-600">
                                {program.excerpt}
                            </p>
                        </Link>
                    ))}
                    {programs.length === 0 && (
                        <p className="text-sm text-neutral-500">
                            No programs yet.
                        </p>
                    )}
                </div>
            </section>

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-6xl px-4 py-12">
                    <h2 className="text-2xl font-bold">Upcoming Events</h2>
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
                                        ? formatDate(event.start_date)
                                        : formatDateTime(event.starts_at)}
                                    {event.is_virtual
                                        ? ' · Virtual'
                                        : event.location_name
                                          ? ` · ${event.location_name}`
                                          : ''}
                                </p>
                            </li>
                        ))}
                        {events.length === 0 && (
                            <p className="py-4 text-sm text-neutral-500">
                                No upcoming events.
                            </p>
                        )}
                    </ul>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-12">
                <h2 className="text-2xl font-bold">Latest News</h2>
                <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.map((post) => (
                        <Link
                            key={post.id}
                            href={`/news/${post.slug}`}
                            className="rounded-lg border p-5 transition hover:border-neutral-400 hover:shadow-sm"
                        >
                            <p className="text-xs text-neutral-500">
                                {formatDate(post.published_at)}
                            </p>
                            <h3 className="mt-1 font-semibold">{post.title}</h3>
                            <p className="mt-2 line-clamp-3 text-sm text-neutral-600">
                                {post.excerpt}
                            </p>
                        </Link>
                    ))}
                    {posts.length === 0 && (
                        <p className="text-sm text-neutral-500">No news yet.</p>
                    )}
                </div>
            </section>

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-6xl px-4 py-12">
                    <h2 className="text-2xl font-bold">Our Team</h2>
                    <div className="mt-6 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                        {team.map((member) => (
                            <div key={member.id} className="text-center">
                                {member.photo?.thumb_url ? (
                                    <img
                                        src={member.photo.thumb_url}
                                        alt={
                                            member.photo.alt_text ?? member.name
                                        }
                                        className="mx-auto size-24 rounded-full object-cover"
                                    />
                                ) : (
                                    <div className="mx-auto flex size-24 items-center justify-center rounded-full bg-neutral-200 text-neutral-500">
                                        {member.name.charAt(0)}
                                    </div>
                                )}
                                <p className="mt-3 font-semibold">
                                    {member.name}
                                </p>
                                <p className="text-sm text-neutral-600">
                                    {member.title}
                                </p>
                            </div>
                        ))}
                        {team.length === 0 && (
                            <p className="text-sm text-neutral-500">
                                No team members yet.
                            </p>
                        )}
                    </div>
                </div>
            </section>
        </>
    );
}
