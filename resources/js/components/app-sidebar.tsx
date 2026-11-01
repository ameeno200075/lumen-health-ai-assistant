import { Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    LayoutDashboard,
    MessageCircle,
    UserCog,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'AI Assistant',
            href: '/assistant',
            icon: MessageCircle,
        },
        {
            title: 'My appointments',
            href: '/appointments',
            icon: CalendarCheck,
        },
        {
            title: 'Overview',
            href: dashboard(),
            icon: LayoutDashboard,
        },
        ...(auth.user?.is_admin
            ? [
                  {
                      title: 'Admin overview',
                      href: '/admin',
                      icon: LayoutDashboard,
                  },
                  {
                      title: 'Doctors',
                      href: '/admin/doctors',
                      icon: UserCog,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
