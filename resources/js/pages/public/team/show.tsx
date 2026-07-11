import { Link } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight } from 'lucide-react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';

type Member = {
    id: number;
    name: string;
    slug: string;
    title: string;
    group: string;
    bio: string;
    photo_url: string | null;
    email: string | null;
    phone: string | null;
};

type Sibling = { name: string; slug: string } | null;

type TeamShowProps = {
    member: Member;
    siblings: { prev: Sibling; next: Sibling };
    seo: Seo;
};

export default function TeamShow({ member, siblings, seo }: TeamShowProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-4xl px-4 py-12">
                <Link
                    href="/about/team"
                    className="inline-flex items-center gap-1 text-sm font-medium text-neutral-500 hover:text-neutral-900"
                >
                    <ArrowLeft className="size-4" /> All team members
                </Link>

                <div className="mt-8 grid gap-8 sm:grid-cols-[200px_1fr]">
                    <div>
                        {member.photo_url ? (
                            <img
                                src={member.photo_url}
                                alt={member.name}
                                className="w-full rounded-xl object-cover"
                            />
                        ) : (
                            <div className="flex aspect-square w-full items-center justify-center rounded-xl bg-neutral-200 text-5xl font-semibold text-neutral-500">
                                {member.name.charAt(0)}
                            </div>
                        )}
                    </div>

                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {member.name}
                        </h1>
                        <p className="mt-1 text-lg text-neutral-600">
                            {member.title}
                        </p>

                        {(member.email || member.phone) && (
                            <div className="mt-3 space-y-1 text-sm text-neutral-600">
                                {member.email && (
                                    <p>
                                        <a
                                            href={`mailto:${member.email}`}
                                            className="underline"
                                        >
                                            {member.email}
                                        </a>
                                    </p>
                                )}
                                {member.phone && <p>{member.phone}</p>}
                            </div>
                        )}

                        <div className="mt-6 space-y-4 leading-relaxed text-neutral-800">
                            {member.bio
                                .split(/\n\n+/)
                                .filter(Boolean)
                                .map((para, i) => (
                                    <p key={i}>{para}</p>
                                ))}
                        </div>
                    </div>
                </div>

                <nav className="mt-12 flex items-center justify-between border-t pt-6">
                    {siblings.prev ? (
                        <Link
                            href={`/about/team/${siblings.prev.slug}`}
                            className="inline-flex items-center gap-1 text-sm font-medium hover:underline"
                        >
                            <ChevronLeft className="size-4" />
                            {siblings.prev.name}
                        </Link>
                    ) : (
                        <span />
                    )}
                    {siblings.next ? (
                        <Link
                            href={`/about/team/${siblings.next.slug}`}
                            className="inline-flex items-center gap-1 text-sm font-medium hover:underline"
                        >
                            {siblings.next.name}
                            <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <span />
                    )}
                </nav>
            </div>
        </>
    );
}
