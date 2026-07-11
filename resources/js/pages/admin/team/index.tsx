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
import type { Paginated, TeamMember } from '@/types/models';

export default function TeamIndex({
    items,
    filters,
}: {
    items: Paginated<TeamMember>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Team members" />
            <PageHeader
                title="Team members"
                description="Staff and board directory shown on the public site."
            >
                <Button asChild>
                    <Link href="/admin/team/create">
                        <Plus className="size-4" /> New team member
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar
                path="/admin/team"
                filters={filters}
                withStatus={false}
            />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Active</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((member) => (
                            <TableRow key={member.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/team/${member.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {member.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {member.title}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            member.is_active
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {member.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(member.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/team/${member.id}/edit`}
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
                                        title={`Delete “${member.name}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/team/${member.id}`,
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
                                    No team members yet.
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
