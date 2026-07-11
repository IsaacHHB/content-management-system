import { Head, router, useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import type { Invite, Paginated } from '@/types/models';

type InviteFormData = {
    email: string;
    role: string;
};

function inviteStatus(invite: Invite): 'Accepted' | 'Expired' | 'Pending' {
    if (invite.accepted_at) {
        return 'Accepted';
    }

    if (new Date(invite.expires_at) < new Date()) {
        return 'Expired';
    }

    return 'Pending';
}

export default function InvitesIndex({ items }: { items: Paginated<Invite> }) {
    const form = useForm<InviteFormData>({ email: '', role: 'editor' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/invites', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <div className="space-y-4 p-4">
            <Head title="Invites" />
            <PageHeader
                title="Invites"
                description="Invite new admins and editors, and track pending invitations."
            />

            <form
                onSubmit={submit}
                className="flex flex-wrap items-end gap-4 rounded-lg border bg-card p-4"
            >
                <div className="min-w-64 flex-1 space-y-1.5">
                    <Label>Email</Label>
                    <Input
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    <InputError message={form.errors.email} />
                </div>
                <div className="space-y-1.5">
                    <Label>Role</Label>
                    <Select
                        value={form.data.role}
                        onValueChange={(role) => form.setData('role', role)}
                    >
                        <SelectTrigger className="w-36">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="admin">Admin</SelectItem>
                            <SelectItem value="editor">Editor</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <Button type="submit" disabled={form.processing}>
                    <Send className="size-4" /> Send invite
                </Button>
            </form>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Invited by</TableHead>
                            <TableHead>Sent</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((invite) => {
                            const status = inviteStatus(invite);
                            const isAccepted = status === 'Accepted';

                            return (
                                <TableRow key={invite.id}>
                                    <TableCell className="font-medium">
                                        {invite.email}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant="outline"
                                            className="capitalize"
                                        >
                                            {invite.role}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                status === 'Accepted'
                                                    ? 'default'
                                                    : status === 'Expired'
                                                      ? 'destructive'
                                                      : 'secondary'
                                            }
                                        >
                                            {status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {invite.inviter?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {formatDate(invite.created_at)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {!isAccepted && (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            `/admin/invites/${invite.id}/resend`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Resend
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            Revoke
                                                        </Button>
                                                    }
                                                    title={`Revoke invite for “${invite.email}”?`}
                                                    description="This invite will no longer be usable."
                                                    confirmLabel="Revoke"
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            `/admin/invites/${invite.id}`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </>
                                        )}
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No invites yet.
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
