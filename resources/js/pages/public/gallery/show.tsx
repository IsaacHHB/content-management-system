import { useState } from 'react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';

type Photo = {
    id: number;
    url: string | null | undefined;
    thumb_url: string | null | undefined;
    alt: string | null;
    caption: string | null;
};

type GalleryShowProps = {
    gallery: {
        id: number;
        title: string;
        description: string | null;
        photos: Photo[];
    };
    seo: Seo;
};

export default function GalleryShow({ gallery, seo }: GalleryShowProps) {
    const [activePhoto, setActivePhoto] = useState<Photo | null>(null);

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    {gallery.title}
                </h1>
                {gallery.description && (
                    <p className="mt-3 max-w-2xl text-neutral-600">
                        {gallery.description}
                    </p>
                )}

                <div className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    {gallery.photos.map((photo) => (
                        <button
                            key={photo.id}
                            type="button"
                            onClick={() => setActivePhoto(photo)}
                            className="aspect-square overflow-hidden rounded-lg bg-neutral-100 focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:outline-none"
                        >
                            <img
                                src={photo.thumb_url ?? photo.url ?? ''}
                                alt={photo.alt ?? ''}
                                className="size-full object-cover transition hover:scale-105"
                            />
                        </button>
                    ))}
                    {gallery.photos.length === 0 && (
                        <p className="text-sm text-neutral-500">
                            No photos yet.
                        </p>
                    )}
                </div>
            </div>

            {activePhoto && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={activePhoto.alt ?? gallery.title}
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                    onClick={() => setActivePhoto(null)}
                >
                    <button
                        type="button"
                        onClick={() => setActivePhoto(null)}
                        className="absolute top-4 right-4 text-2xl leading-none text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                    <figure
                        className="max-h-full max-w-full"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <img
                            src={activePhoto.url ?? activePhoto.thumb_url ?? ''}
                            alt={activePhoto.alt ?? ''}
                            className="max-h-[80vh] max-w-full rounded object-contain"
                        />
                        {activePhoto.caption && (
                            <figcaption className="mt-2 text-center text-sm text-neutral-300">
                                {activePhoto.caption}
                            </figcaption>
                        )}
                    </figure>
                </div>
            )}
        </>
    );
}
