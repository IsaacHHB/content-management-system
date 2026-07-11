import { Link } from '@inertiajs/react';

import { BlockRenderer } from '@/blocks/block-renderer';
import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { formatDate } from '@/lib/format';
import type { Block, Category } from '@/types/models';

type NewsShowProps = {
    post: {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        published_at: string | null;
        author?: { id: number; name: string } | null;
        categories: Category[];
        blocks: Block[];
    };
    seo: Seo;
};

export default function NewsShow({ post, seo }: NewsShowProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <article className="mx-auto max-w-3xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    {post.title}
                </h1>

                <p className="mt-3 text-sm text-neutral-500">
                    {formatDate(post.published_at)}
                    {post.author?.name && ` · by ${post.author.name}`}
                </p>

                {post.categories.length > 0 && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        {post.categories.map((category) => (
                            <Link
                                key={category.id}
                                href={`/news?category=${category.slug}`}
                                className="rounded-full border px-3 py-1 text-xs text-neutral-600 hover:border-neutral-400"
                            >
                                {category.name}
                            </Link>
                        ))}
                    </div>
                )}

                {post.excerpt && (
                    <p className="mt-6 text-lg text-neutral-600">
                        {post.excerpt}
                    </p>
                )}

                <div className="mt-6">
                    <BlockRenderer blocks={post.blocks} />
                </div>
            </article>
        </>
    );
}
