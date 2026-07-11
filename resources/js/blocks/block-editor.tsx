import { LayoutList, MousePointerClick } from 'lucide-react';
import { useState } from 'react';

import { BlockBuilder } from '@/blocks/block-builder';
import { PageCanvas } from '@/blocks/page-canvas';
import { cn } from '@/lib/utils';
import type { Block } from '@/types/models';

type Mode = 'form' | 'visual';

/**
 * Toggle between two block-editing experiences over the same blocks array:
 *  - "Form"   — the structured block-builder (collapsible cards + settings).
 *  - "Visual" — the page rendered inside the real public layout (header, footer,
 *    full width) with a device switcher and an Edit / Preview toggle. Stays in
 *    the admin as a draft/staging surface; changes go live on publish.
 */
export function BlockEditor({
    value,
    onChange,
    title,
    showPreviewTitle = true,
}: {
    value: Block[];
    onChange: (blocks: Block[]) => void;
    title?: string;
    showPreviewTitle?: boolean;
}) {
    const [mode, setMode] = useState<Mode>('form');

    return (
        <div className="space-y-3">
            <div className="inline-flex rounded-md border p-0.5 text-sm">
                <button
                    type="button"
                    onClick={() => setMode('form')}
                    className={cn(
                        'flex items-center gap-1.5 rounded px-3 py-1 transition',
                        mode === 'form'
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <LayoutList className="size-4" /> Form
                </button>
                <button
                    type="button"
                    onClick={() => setMode('visual')}
                    className={cn(
                        'flex items-center gap-1.5 rounded px-3 py-1 transition',
                        mode === 'visual'
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <MousePointerClick className="size-4" /> Visual
                </button>
            </div>

            {mode === 'form' ? (
                <BlockBuilder value={value} onChange={onChange} />
            ) : (
                <PageCanvas
                    value={value}
                    onChange={onChange}
                    title={title}
                    showTitle={showPreviewTitle}
                />
            )}
        </div>
    );
}
