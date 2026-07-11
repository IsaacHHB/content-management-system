import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { IndexToolbar } from '@/components/cms/index-toolbar';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import { StatusBadge } from '@/components/cms/status-badge';
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
import type { Paginated, Post } from '@/types/models';

export default function PostsIndex({
    items,
    filters,
}: {
    items: Paginated<Post>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="News" />
            <PageHeader title="News" description="Posts and announcements.">
                <Button asChild>
                    <Link href="/admin/posts/create">
                        <Plus className="size-4" /> New post
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar path="/admin/posts" filters={filters} />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Featured</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((post) => (
                            <TableRow key={post.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/posts/${post.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {post.title}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    {post.is_featured && (
                                        <Badge>Featured</Badge>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={post.status} />
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(post.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/posts/${post.id}/edit`}
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
                                        title={`Delete “${post.title}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/posts/${post.id}`,
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
                                    No posts yet.
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
