import { BlockRenderer } from '@/blocks/block-renderer';
import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import type { Block } from '@/types/models';

type PageProps = {
    page: { id: number; title: string; blocks: Block[] };
    seo: Seo;
};

export default function Page({ page, seo }: PageProps) {
    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    {page.title}
                </h1>
                <div className="mt-6">
                    <BlockRenderer blocks={page.blocks} />
                </div>
            </div>
        </>
    );
}
