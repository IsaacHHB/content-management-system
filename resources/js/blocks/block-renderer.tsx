import { BLOCKS } from '@/blocks/registry';
import type { Block } from '@/types/models';

export function BlockRenderer({ blocks }: { blocks: Block[] }) {
    return (
        <>
            {(blocks ?? []).map((block) => {
                const def = BLOCKS[block.type];

                if (!def) {
                    return null;
                }

                const Render = def.Render;

                return (
                    <div key={block.id} className="py-6">
                        <Render data={block.data} />
                    </div>
                );
            })}
        </>
    );
}
