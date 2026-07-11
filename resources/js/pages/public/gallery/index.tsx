import { Link } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';

type GallerySummary = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    cover: string | null | undefined;
    count: number;
};

type GalleryIndexProps = {
    galleries: GallerySummary[];
    seo: Seo;
};

export default function GalleryIndex({ galleries, seo }: GalleryIndexProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">Gallery</h1>

                <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {galleries.map((gallery) => (
                        <Link
                            key={gallery.id}
                            href={`/gallery/${gallery.slug}`}
                            className="group overflow-hidden rounded-lg border transition hover:border-neutral-400 hover:shadow-sm"
                        >
                            <div className="aspect-video w-full overflow-hidden bg-neutral-100">
                                {gallery.cover ? (
                                    <img
                                        src={gallery.cover}
                                        alt=""
                                        className="size-full object-cover transition group-hover:scale-105"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-sm text-neutral-400">
                                        No photos
                                    </div>
                                )}
                            </div>
                            <div className="p-4">
                                <h2 className="font-semibold">
                                    {gallery.title}
                                </h2>
                                {gallery.description && (
                                    <p className="mt-1 line-clamp-2 text-sm text-neutral-600">
                                        {gallery.description}
                                    </p>
                                )}
                                <p className="mt-2 text-xs text-neutral-500">
                                    {gallery.count}{' '}
                                    {gallery.count === 1 ? 'photo' : 'photos'}
                                </p>
                            </div>
                        </Link>
                    ))}
                    {galleries.length === 0 && (
                        <p className="text-sm text-neutral-500">
                            No galleries yet.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
