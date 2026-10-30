import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Fuel,
    LayoutGrid,
    LineChart,
    MapPin,
    Receipt,
    Trash2,
    Users,
    UsersRound,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { LanguageSwitcher } from '@/components/language-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { StationSwitcher } from '@/components/station-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useLocale } from '@/hooks/use-locale';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import { index as analyticsIndex } from '@/routes/analytics';
import { index as cashFlowIndex } from '@/routes/cash-flow';
import { index as customersIndex } from '@/routes/customers';
import { index as deletionRequestsIndex } from '@/routes/deletion-requests';
import { index as fuelTanksIndex } from '@/routes/fuel/tanks';
import { index as reportsIndex } from '@/routes/reports';
import { index as salesIndex } from '@/routes/sales';
import { index as stationsIndex } from '@/routes/stations';
import { index as teamIndex } from '@/routes/team';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useLocale();
    const { can } = usePermissions();
    const { version, pending_deletion_requests: pendingDeletionRequests } =
        usePage().props;

    const navItems: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.sales'),
            href: salesIndex(),
            icon: Receipt,
            permission: 'sales',
        },
        {
            title: t('nav.fuel_management'),
            href: fuelTanksIndex(),
            icon: Fuel,
            permission: 'fuel_management',
        },
        {
            title: t('nav.cash_flow'),
            href: cashFlowIndex(),
            icon: Wallet,
            permission: 'cash_flow',
        },
        {
            title: t('nav.sales_analysis'),
            href: analyticsIndex(),
            icon: LineChart,
            permission: 'sales_analysis',
        },
        {
            title: t('nav.reports'),
            href: reportsIndex(),
            icon: BookOpen,
            permission: 'reports',
        },
        {
            title: t('nav.stations'),
            href: stationsIndex(),
            icon: MapPin,
            permission: 'stations',
        },
        {
            title: t('nav.team'),
            href: teamIndex(),
            icon: UsersRound,
        },
        {
            title: t('nav.customers'),
            href: customersIndex(),
            icon: Users,
            permission: 'customers',
        },
        {
            title: t('nav.deletion_requests'),
            href: deletionRequestsIndex(),
            icon: Trash2,
            permission: 'delete_records',
            badge: pendingDeletionRequests as number,
        },
    ];

    const mainNavItems = navItems.filter(
        (item) => !item.permission || can(item.permission),
    );

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
                <StationSwitcher />
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <div className="px-2 pb-1">
                    <LanguageSwitcher />
                </div>
                <NavUser />
                <p className="px-2 pt-1 text-center text-[10px] text-muted-foreground group-data-[collapsible=icon]:hidden">
                    v{version}
                </p>
            </SidebarFooter>
        </Sidebar>
    );
}
