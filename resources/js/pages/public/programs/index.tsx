import { Link } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';

type ProgramSummary = {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
};

type ProgramsIndexProps = {
    programs: ProgramSummary[];
    seo: Seo;
};

export default function ProgramsIndex({ programs, seo }: ProgramsIndexProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    Our Programs
                </h1>

                <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {programs.map((program) => (
                        <Link
                            key={program.id}
                            href={`/programs/${program.slug}`}
                            className="rounded-lg border p-5 transition hover:border-neutral-400 hover:shadow-sm"
                        >
                            <h2 className="font-semibold">{program.title}</h2>
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
            </div>
        </>
    );
}
