import { Head } from '@inertiajs/react';

import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Activity, Paginated } from '@/types/models';

export default function ActivityIndex({
    items,
}: {
    items: Paginated<Activity>;
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Activity log" />
            <PageHeader
                title="Activity log"
                description="A record of changes made across the CMS."
            />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Description</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead>By</TableHead>
                            <TableHead>When</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((activity) => (
                            <TableRow key={activity.id}>
                                <TableCell className="font-medium">
                                    {activity.description}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {subjectLabel(activity)}
                                </TableCell>
                                <TableCell>
                                    {activity.causer?.name ?? 'System'}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(activity.created_at)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No activity recorded yet.
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

function subjectLabel(activity: Activity): string {
    if (!activity.subject_type) {
        return '—';
    }

    const basename =
        activity.subject_type.split('\\').pop() ?? activity.subject_type;

    return activity.subject_id
        ? `${basename} #${activity.subject_id}`
        : basename;
}
