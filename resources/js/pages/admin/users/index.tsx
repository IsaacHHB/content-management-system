import { Head, router } from '@inertiajs/react';

import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types/models';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_active?: boolean;
    last_login_at?: string | null;
    roles: { id: number; name: string }[];
};

export default function UsersIndex({
    items,
    assignableRoles,
}: {
    items: Paginated<ManagedUser>;
    assignableRoles: string[];
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Users" />
            <PageHeader
                title="Users"
                description="Manage admin accounts, roles, and access."
            />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Active</TableHead>
                            <TableHead>Last login</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((user) => (
                            <TableRow key={user.id}>
                                <TableCell className="font-medium">
                                    {user.name}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {user.email}
                                </TableCell>
                                <TableCell>
                                    <Select
                                        value={user.roles[0]?.name ?? ''}
                                        onValueChange={(role) =>
                                            router.put(
                                                `/admin/users/${user.id}`,
                                                { role },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-40">
                                            <SelectValue placeholder="Select role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {assignableRoles.map((role) => (
                                                <SelectItem
                                                    key={role}
                                                    value={role}
                                                >
                                                    {role}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell>
                                    <Switch
                                        checked={Boolean(user.is_active)}
                                        onCheckedChange={(is_active) =>
                                            router.put(
                                                `/admin/users/${user.id}`,
                                                { is_active },
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(user.last_login_at)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No users yet.
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
