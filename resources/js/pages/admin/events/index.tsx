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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatDateOnly, formatDateTime } from '@/lib/format';
import type { NdnEvent, Paginated } from '@/types/models';

function isUpcoming(event: NdnEvent, now: Date, today: string): boolean {
    if (event.all_day) {
        const d = event.end_date ?? event.start_date;

        return Boolean(d) && d! >= today;
    }

    const d = event.ends_at ?? event.starts_at;

    return Boolean(d) && new Date(d!) >= now;
}

function eventDateLabel(event: NdnEvent): string {
    if (event.all_day) {
        const start = formatDateOnly(event.start_date);

        return event.end_date
            ? `${start} – ${formatDateOnly(event.end_date)}`
            : start;
    }

    const start = formatDateTime(event.starts_at, event.timezone);

    return event.ends_at
        ? `${start} – ${formatDateTime(event.ends_at, event.timezone)}`
        : start;
}

function EventsTable({
    events,
    emptyMessage,
}: {
    events: NdnEvent[];
    emptyMessage: string;
}) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {events.map((event) => (
                        <TableRow key={event.id}>
                            <TableCell className="font-medium">
                                <Link
                                    href={`/admin/events/${event.id}/edit`}
                                    className="hover:underline"
                                >
                                    {event.title}
                                </Link>
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {eventDateLabel(event)}
                            </TableCell>
                            <TableCell>
                                <StatusBadge status={event.status} />
                            </TableCell>
                            <TableCell className="text-right">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={`/admin/events/${event.id}/edit`}
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
                                    title={`Delete “${event.title}”?`}
                                    description="It will be moved to trash and can be restored by a super administrator."
                                    confirmLabel="Delete"
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            `/admin/events/${event.id}`,
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </TableCell>
                        </TableRow>
                    ))}
                    {events.length === 0 && (
                        <TableRow>
                            <TableCell
                                colSpan={4}
                                className="py-8 text-center text-muted-foreground"
                            >
                                {emptyMessage}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

export default function EventsIndex({
    items,
    filters,
}: {
    items: Paginated<NdnEvent>;
    filters: { search?: string; status?: string };
}) {
    const now = new Date();
    const today = now.toISOString().slice(0, 10);

    const upcoming = items.data.filter((event) =>
        isUpcoming(event, now, today),
    );
    const past = items.data.filter((event) => !isUpcoming(event, now, today));

    return (
        <div className="space-y-4 p-4">
            <Head title="Events" />
            <PageHeader title="Events" description="Upcoming and past events.">
                <Button asChild>
                    <Link href="/admin/events/create">
                        <Plus className="size-4" /> New event
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar path="/admin/events" filters={filters} />

            <Tabs defaultValue="upcoming">
                <TabsList>
                    <TabsTrigger value="upcoming">Upcoming</TabsTrigger>
                    <TabsTrigger value="past">Past</TabsTrigger>
                </TabsList>
                <TabsContent value="upcoming" className="space-y-4">
                    <EventsTable
                        events={upcoming}
                        emptyMessage="No upcoming events."
                    />
                </TabsContent>
                <TabsContent value="past" className="space-y-4">
                    <EventsTable events={past} emptyMessage="No past events." />
                </TabsContent>
            </Tabs>

            <Pagination meta={items} />
        </div>
    );
}
