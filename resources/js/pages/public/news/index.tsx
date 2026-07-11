import { Link } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Category, Paginated, Post } from '@/types/models';

type NewsIndexProps = {
    posts: Paginated<Post>;
    categories: Category[];
    activeCategory: string;
    seo: Seo;
};

export default function NewsIndex({
    posts,
    categories,
    activeCategory,
    seo,
}: NewsIndexProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">News</h1>

                <nav
                    className="mt-6 flex flex-wrap gap-2 text-sm"
                    aria-label="Filter by category"
                >
                    <Link
                        href="/news"
                        className={cn(
                            'rounded-full border px-3 py-1',
                            !activeCategory &&
                                'border-neutral-900 bg-neutral-900 text-white',
                        )}
                    >
                        All
                    </Link>
                    {categories.map((category) => (
                        <Link
                            key={category.id}
                            href={`/news?category=${category.slug}`}
                            className={cn(
                                'rounded-full border px-3 py-1',
                                activeCategory === category.slug &&
                                    'border-neutral-900 bg-neutral-900 text-white',
                            )}
                        >
                            {category.name}
                        </Link>
                    ))}
                </nav>

                <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.data.map((post) => (
                        <Link
                            key={post.id}
                            href={`/news/${post.slug}`}
                            className="rounded-lg border p-5 transition hover:border-neutral-400 hover:shadow-sm"
                        >
                            <p className="text-xs text-neutral-500">
                                {formatDate(post.published_at)}
                            </p>
                            <h2 className="mt-1 font-semibold">{post.title}</h2>
                            <p className="mt-2 line-clamp-3 text-sm text-neutral-600">
                                {post.excerpt}
                            </p>
                        </Link>
                    ))}
                    {posts.data.length === 0 && (
                        <p className="text-sm text-neutral-500">No news yet.</p>
                    )}
                </div>

                {posts.last_page > 1 && (
                    <nav className="mt-10 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span className="text-neutral-500">
                            Showing {posts.from ?? 0}–{posts.to ?? 0} of{' '}
                            {posts.total}
                        </span>
                        <div className="flex flex-wrap gap-1">
                            {posts.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    preserveScroll
                                    preserveState
                                    className={cn(
                                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2',
                                        link.active &&
                                            'border-neutral-900 bg-neutral-900 text-white',
                                        !link.url &&
                                            'pointer-events-none opacity-50',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    </nav>
                )}
            </div>
        </>
    );
}
