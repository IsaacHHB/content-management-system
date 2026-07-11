import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Calendar,
    FileText,
    Handshake,
    Image,
    Images,
    LayoutGrid,
    Mail,
    Menu as MenuIcon,
    Newspaper,
    Settings,
    Boxes,
    UserCog,
    UserPlus,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';

type Item = {
    title: string;
    href: string;
    icon: typeof LayoutGrid;
    can?: string;
    role?: string[];
};

const groups: { label: string; items: Item[] }[] = [
    {
        label: 'Content',
        items: [
            { title: 'Dashboard', href: '/admin', icon: LayoutGrid },
            {
                title: 'Pages',
                href: '/admin/pages',
                icon: FileText,
                can: 'pages.view',
            },
            {
                title: 'Programs',
                href: '/admin/programs',
                icon: Boxes,
                can: 'programs.view',
            },
            {
                title: 'Events',
                href: '/admin/events',
                icon: Calendar,
                can: 'events.view',
            },
            {
                title: 'News',
                href: '/admin/posts',
                icon: Newspaper,
                can: 'posts.view',
            },
            {
                title: 'Galleries',
                href: '/admin/galleries',
                icon: Images,
                can: 'galleries.view',
            },
            {
                title: 'Team',
                href: '/admin/team',
                icon: Users,
                can: 'team.view',
            },
            {
                title: 'Partners',
                href: '/admin/partners',
                icon: Handshake,
                can: 'partners.view',
            },
            {
                title: 'Media',
                href: '/admin/media',
                icon: Image,
                can: 'media.manage',
            },
        ],
    },
    {
        label: 'Site',
        items: [
            {
                title: 'Menus',
                href: '/admin/menus',
                icon: MenuIcon,
                can: 'menus.manage',
            },
            {
                title: 'Settings',
                href: '/admin/settings',
                icon: Settings,
                can: 'settings.manage',
            },
            {
                title: 'Contact inbox',
                href: '/admin/contacts',
                icon: Mail,
                can: 'contacts.manage',
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                title: 'Users',
                href: '/admin/users',
                icon: UserCog,
                role: ['super_admin', 'admin'],
            },
            {
                title: 'Invites',
                href: '/admin/invites',
                icon: UserPlus,
                role: ['super_admin', 'admin'],
            },
            {
                title: 'Activity',
                href: '/admin/activity',
                icon: Activity,
                can: 'activity.view',
            },
        ],
    },
];

export function AppSidebar() {
    const { isCurrentUrl } = useCurrentUrl();
    const auth = usePage().props.auth;
    const permissions = auth.user?.permissions ?? [];
    const roles = auth.user?.roles ?? [];

    const allowed = (item: Item) =>
        (!item.can || permissions.includes(item.can)) &&
        (!item.role || item.role.some((r) => roles.includes(r)));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => {
                    const items = group.items.filter(allowed);

                    if (items.length === 0) {
                        return null;
                    }

                    return (
                        <SidebarGroup key={group.label} className="px-2 py-0">
                            <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                            <SidebarMenu>
                                {items.map((item) => (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={
                                                item.href === '/admin'
                                                    ? isCurrentUrl('/admin')
                                                    : isCurrentUrl(item.href)
                                            }
                                            tooltip={{ children: item.title }}
                                        >
                                            <Link href={item.href} prefetch>
                                                <item.icon />
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
