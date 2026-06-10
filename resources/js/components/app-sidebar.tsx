import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    Fuel,
    LayoutGrid,
    LineChart,
    Receipt,
    Wallet,
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
import { index as analyticsIndex } from '@/routes/analytics';
import { index as cashFlowIndex } from '@/routes/cash-flow';
import { index as fuelTanksIndex } from '@/routes/fuel/tanks';
import { index as reportsIndex } from '@/routes/reports';
import { index as salesIndex } from '@/routes/sales';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Sales',
        href: salesIndex(),
        icon: Receipt,
    },
    {
        title: 'Fuel Management',
        href: fuelTanksIndex(),
        icon: Fuel,
    },
    {
        title: 'Cash Flow',
        href: cashFlowIndex(),
        icon: Wallet,
    },
    {
        title: 'Sales Analysis',
        href: analyticsIndex(),
        icon: LineChart,
    },
    {
        title: 'Reports',
        href: reportsIndex(),
        icon: BookOpen,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
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
