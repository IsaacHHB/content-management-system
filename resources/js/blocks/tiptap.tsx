import Link from '@tiptap/extension-link';
import { EditorContent, useEditor } from '@tiptap/react';
import type { Content } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { Toggle } from '@/components/ui/toggle';
import { cn } from '@/lib/utils';
import type { TiptapDoc } from '@/types/models';

const EMPTY: TiptapDoc = { type: 'doc', content: [{ type: 'paragraph' }] };

// --- Read-only renderer (matches the server-side allowlist) ---

type Node = {
    type: string;
    text?: string;
    attrs?: Record<string, unknown>;
    content?: Node[];
    marks?: { type: string; attrs?: Record<string, unknown> }[];
};

function renderText(node: Node, key: number): ReactNode {
    let el: ReactNode = node.text ?? '';

    for (const mark of node.marks ?? []) {
        if (mark.type === 'bold') {
            el = <strong>{el}</strong>;
        } else if (mark.type === 'italic') {
            el = <em>{el}</em>;
        } else if (mark.type === 'link') {
            const href = String(mark.attrs?.href ?? '#');
            el = (
                <a
                    href={href}
                    className="text-primary underline"
                    rel="noreferrer"
                >
                    {el}
                </a>
            );
        }
    }

    return <span key={key}>{el}</span>;
}

function renderNode(node: Node, key: number): ReactNode {
    const kids = () => node.content?.map((c, i) => renderNode(c, i));

    switch (node.type) {
        case 'doc':
            return <>{kids()}</>;
        case 'paragraph':
            return <p key={key}>{kids()}</p>;
        case 'heading':
            return node.attrs?.level === 3 ? (
                <h3 key={key}>{kids()}</h3>
            ) : (
                <h2 key={key}>{kids()}</h2>
            );
        case 'bulletList':
            return <ul key={key}>{kids()}</ul>;
        case 'orderedList':
            return <ol key={key}>{kids()}</ol>;
        case 'listItem':
            return <li key={key}>{kids()}</li>;
        case 'blockquote':
            return <blockquote key={key}>{kids()}</blockquote>;
        case 'horizontalRule':
            return <hr key={key} />;
        case 'hardBreak':
            return <br key={key} />;
        case 'text':
            return renderText(node, key);
        default:
            return null;
    }
}

export function TiptapContent({
    doc,
    className,
}: {
    doc: TiptapDoc | null | undefined;
    className?: string;
}) {
    if (!doc || !doc.content) {
        return null;
    }

    return <div className={className}>{renderNode(doc as Node, 0)}</div>;
}

// --- Editor ---

function extensions() {
    return [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            codeBlock: false,
            code: false,
            strike: false,
        }),
        Link.configure({ openOnClick: false, autolink: false }),
    ];
}

export function TiptapEditor({
    value,
    onChange,
    seamless = false,
}: {
    value: TiptapDoc | null | undefined;
    onChange: (doc: TiptapDoc) => void;
    // seamless: no border/chrome and the toolbar only appears on focus, so the
    // text reads like the published page until an editor clicks into it. Used by
    // the visual (staging) editor.
    seamless?: boolean;
}) {
    const [focused, setFocused] = useState(false);
    const editor = useEditor({
        extensions: extensions(),
        content: (value ?? EMPTY) as Content,
        editorProps: {
            attributes: {
                class: seamless
                    ? 'prose max-w-none focus:outline-none'
                    : 'prose prose-sm max-w-none min-h-32 rounded-md border p-3 focus:outline-none',
            },
        },
        onFocus: () => setFocused(true),
        onBlur: () => setFocused(false),
        onUpdate: ({ editor }) => onChange(editor.getJSON() as TiptapDoc),
    });

    if (!editor) {
        return null;
    }

    const showToolbar = !seamless || focused;

    return (
        <div className={cn('space-y-2', seamless && 'relative')}>
            <div
                // preventDefault on mousedown keeps focus in the editor so the
                // toolbar doesn't blur-and-vanish before the click registers.
                onMouseDown={(e) => e.preventDefault()}
                className={cn(
                    'flex flex-wrap gap-1',
                    seamless &&
                        'absolute -top-11 left-0 z-20 rounded-md border bg-popover p-1 shadow-md',
                    seamless && !showToolbar && 'hidden',
                )}
            >
                <Toggle
                    size="sm"
                    pressed={editor.isActive('bold')}
                    onPressedChange={() =>
                        editor.chain().focus().toggleBold().run()
                    }
                >
                    <Bold className="size-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('italic')}
                    onPressedChange={() =>
                        editor.chain().focus().toggleItalic().run()
                    }
                >
                    <Italic className="size-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('heading', { level: 2 })}
                    onPressedChange={() =>
                        editor.chain().focus().toggleHeading({ level: 2 }).run()
                    }
                >
                    H2
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('heading', { level: 3 })}
                    onPressedChange={() =>
                        editor.chain().focus().toggleHeading({ level: 3 }).run()
                    }
                >
                    H3
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('bulletList')}
                    onPressedChange={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                >
                    <List className="size-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('orderedList')}
                    onPressedChange={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                >
                    <ListOrdered className="size-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('blockquote')}
                    onPressedChange={() =>
                        editor.chain().focus().toggleBlockquote().run()
                    }
                >
                    <Quote className="size-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('link')}
                    onPressedChange={() => {
                        if (editor.isActive('link')) {
                            editor.chain().focus().unsetLink().run();

                            return;
                        }

                        const url =
                            window.prompt('Link URL (https://… or /path)') ??
                            '';

                        if (url) {
                            editor.chain().focus().setLink({ href: url }).run();
                        }
                    }}
                >
                    <LinkIcon className="size-4" />
                </Toggle>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}
