import { router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export function CategoryManager({
    categories,
}: {
    categories: { id: number; name: string }[];
}) {
    const [name, setName] = useState('');
    const [processing, setProcessing] = useState(false);

    const add = () => {
        if (!name.trim()) {
            return;
        }

        setProcessing(true);
        router.post(
            '/admin/categories',
            { name },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['categories'],
                onSuccess: () => setName(''),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const remove = (id: number) => {
        router.delete(`/admin/categories/${id}`, {
            preserveScroll: true,
            preserveState: true,
            only: ['categories'],
        });
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2">
                <Input
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="New category…"
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            add();
                        }
                    }}
                />
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    disabled={processing}
                    onClick={add}
                >
                    <Plus className="size-4" />
                </Button>
            </div>
            <ul className="space-y-1">
                {categories.map((category) => (
                    <li
                        key={category.id}
                        className="flex items-center justify-between gap-2 text-sm"
                    >
                        <span className="truncate">{category.name}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => remove(category.id)}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </li>
                ))}
                {categories.length === 0 && (
                    <li className="text-sm text-muted-foreground">
                        No categories yet.
                    </li>
                )}
            </ul>
        </div>
    );
}
