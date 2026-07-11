import { Badge } from '@/components/ui/badge';
import type { PublishStatus } from '@/types/models';

const map: Record<
    PublishStatus,
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    published: { label: 'Published', variant: 'default' },
    draft: { label: 'Draft', variant: 'secondary' },
    archived: { label: 'Archived', variant: 'outline' },
};

export function StatusBadge({ status }: { status: PublishStatus }) {
    const cfg = map[status] ?? map.draft;

    return <Badge variant={cfg.variant}>{cfg.label}</Badge>;
}
