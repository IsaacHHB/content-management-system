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
import type { Paginated, Program } from '@/types/models';

export default function ProgramsIndex({
    items,
    filters,
}: {
    items: Paginated<Program>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Programs" />
            <PageHeader
                title="Programs"
                description="Fatherhood, youth, and community programming."
            >
                <Button asChild>
                    <Link href="/admin/programs/create">
                        <Plus className="size-4" /> New program
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar path="/admin/programs" filters={filters} />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((program) => (
                            <TableRow key={program.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/programs/${program.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {program.title}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={program.status} />
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(program.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/programs/${program.id}/edit`}
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
                                        title={`Delete “${program.title}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/programs/${program.id}`,
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
                                    colSpan={4}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No programs yet.
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
