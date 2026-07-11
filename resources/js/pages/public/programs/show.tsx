import { BlockRenderer } from '@/blocks/block-renderer';
import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import type { Block } from '@/types/models';

type ProgramShowProps = {
    program: {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        contact_name: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        external_url: string | null;
        blocks: Block[];
    };
    seo: Seo;
};

export default function ProgramShow({ program, seo }: ProgramShowProps) {
    const hasContact =
        program.contact_name ||
        program.contact_email ||
        program.contact_phone ||
        program.external_url;

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    {program.title}
                </h1>
                {program.excerpt && (
                    <p className="mt-4 text-lg text-neutral-600">
                        {program.excerpt}
                    </p>
                )}

                <div className="mt-8 grid gap-10 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <BlockRenderer blocks={program.blocks} />
                    </div>

                    {hasContact && (
                        <aside className="h-fit rounded-lg border p-5">
                            <h2 className="font-semibold">Contact</h2>
                            <dl className="mt-3 space-y-2 text-sm">
                                {program.contact_name && (
                                    <div>
                                        <dt className="text-neutral-500">
                                            Name
                                        </dt>
                                        <dd>{program.contact_name}</dd>
                                    </div>
                                )}
                                {program.contact_email && (
                                    <div>
                                        <dt className="text-neutral-500">
                                            Email
                                        </dt>
                                        <dd>
                                            <a
                                                href={`mailto:${program.contact_email}`}
                                                className="hover:underline"
                                            >
                                                {program.contact_email}
                                            </a>
                                        </dd>
                                    </div>
                                )}
                                {program.contact_phone && (
                                    <div>
                                        <dt className="text-neutral-500">
                                            Phone
                                        </dt>
                                        <dd>
                                            <a
                                                href={`tel:${program.contact_phone}`}
                                                className="hover:underline"
                                            >
                                                {program.contact_phone}
                                            </a>
                                        </dd>
                                    </div>
                                )}
                                {program.external_url && (
                                    <div>
                                        <dt className="text-neutral-500">
                                            Website
                                        </dt>
                                        <dd>
                                            <a
                                                href={program.external_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="hover:underline"
                                            >
                                                {program.external_url}
                                            </a>
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </aside>
                    )}
                </div>
            </div>
        </>
    );
}
