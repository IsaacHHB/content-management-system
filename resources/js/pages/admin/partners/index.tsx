import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { IndexToolbar } from '@/components/cms/index-toolbar';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import { Badge } from '@/components/ui/badge';
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
import type { Paginated, Partner } from '@/types/models';

export default function PartnersIndex({
    items,
    filters,
}: {
    items: Paginated<Partner>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Partners" />
            <PageHeader
                title="Partners"
                description="Funders and partner organizations shown on the public site."
            >
                <Button asChild>
                    <Link href="/admin/partners/create">
                        <Plus className="size-4" /> New partner
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar
                path="/admin/partners"
                filters={filters}
                withStatus={false}
            />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Logo</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Website</TableHead>
                            <TableHead>Active</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((partner) => (
                            <TableRow key={partner.id}>
                                <TableCell>
                                    {partner.logo?.thumb_url ? (
                                        <img
                                            src={partner.logo.thumb_url}
                                            alt={partner.name}
                                            className="h-8 w-auto max-w-24 object-contain"
                                        />
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/partners/${partner.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {partner.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="max-w-56 truncate text-muted-foreground">
                                    {partner.website_url ?? '—'}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            partner.is_active
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {partner.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(partner.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/partners/${partner.id}/edit`}
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
                                        title={`Delete “${partner.name}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/partners/${partner.id}`,
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
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No partners yet.
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
