import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { Fragment, useState } from 'react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
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
import type { ContactSubmission, Paginated } from '@/types/models';

export default function ContactsIndex({
    items,
    filters,
    unread_count,
}: {
    items: Paginated<ContactSubmission>;
    filters: { unread?: boolean };
    unread_count: number;
}) {
    const [expanded, setExpanded] = useState<number | null>(null);

    const toggleUnreadFilter = () => {
        router.get('/admin/contacts', filters.unread ? {} : { unread: '1' }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleRead = (contact: ContactSubmission) => {
        router.put(
            `/admin/contacts/${contact.id}`,
            { is_read: !contact.is_read },
            { preserveScroll: true },
        );
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="Contact inbox" />
            <PageHeader
                title="Contact inbox"
                description={`${unread_count} unread ${unread_count === 1 ? 'message' : 'messages'}.`}
            >
                <Button
                    variant={filters.unread ? 'default' : 'outline'}
                    onClick={toggleUnreadFilter}
                >
                    {filters.unread
                        ? 'Showing unread only'
                        : 'Show unread only'}
                </Button>
            </PageHeader>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-8" />
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((contact) => (
                            <Fragment key={contact.id}>
                                <TableRow
                                    className="cursor-pointer"
                                    onClick={() =>
                                        setExpanded(
                                            expanded === contact.id
                                                ? null
                                                : contact.id,
                                        )
                                    }
                                >
                                    <TableCell>
                                        {expanded === contact.id ? (
                                            <ChevronDown className="size-4" />
                                        ) : (
                                            <ChevronRight className="size-4" />
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        <div className="flex items-center gap-2">
                                            {contact.name}
                                            {!contact.is_read && (
                                                <Badge>Unread</Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>{contact.email}</TableCell>
                                    <TableCell>
                                        {contact.subject ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {formatDateTime(contact.created_at)}
                                    </TableCell>
                                    <TableCell
                                        className="text-right"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => toggleRead(contact)}
                                        >
                                            {contact.is_read
                                                ? 'Mark unread'
                                                : 'Mark read'}
                                        </Button>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    Delete
                                                </Button>
                                            }
                                            title={`Delete message from ${contact.name}?`}
                                            description="This submission will be permanently deleted."
                                            confirmLabel="Delete"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    `/admin/contacts/${contact.id}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </TableCell>
                                </TableRow>
                                {expanded === contact.id && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="bg-muted/30 whitespace-pre-wrap"
                                        >
                                            {contact.message}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </Fragment>
                        ))}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No contact submissions yet.
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
