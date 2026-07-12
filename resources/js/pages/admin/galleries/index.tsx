import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { IndexToolbar } from '@/components/cms/index-toolbar';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import { StatusBadge } from '@/components/cms/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Gallery, Paginated } from '@/types/models';

export default function GalleriesIndex({
    items,
    filters,
}: {
    items: Paginated<Gallery>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Galleries" />
            <PageHeader
                title="Galleries"
                description="Photo galleries shown across the site."
            >
                <Button asChild>
                    <Link href="/admin/galleries/create">
                        <Plus className="size-4" /> New gallery
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar path="/admin/galleries" filters={filters} />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Photos</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((gallery) => (
                            <TableRow key={gallery.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/galleries/${gallery.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {gallery.title}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={gallery.status} />
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {gallery.media_assets_count ?? '—'}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(gallery.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/galleries/${gallery.id}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" size="icon">
                                                <Trash2 className="size-4" />
                                            </Button>
                                        }
                                        title={`Delete “${gallery.title}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/galleries/${gallery.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No galleries yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <Pagination meta={items} />
        </div>
    );
}
