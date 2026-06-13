import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    Fuel,
    LayoutGrid,
    LineChart,
    MapPin,
    Receipt,
    Users,
    UsersRound,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { LanguageSwitcher } from '@/components/language-switcher';
import { StationSwitcher } from '@/components/station-switcher';
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
import { useLocale } from '@/hooks/use-locale';
import { dashboard } from '@/routes';
import { index as analyticsIndex } from '@/routes/analytics';
import { index as cashFlowIndex } from '@/routes/cash-flow';
import { index as customersIndex } from '@/routes/customers';
import { index as fuelTanksIndex } from '@/routes/fuel/tanks';
import { index as reportsIndex } from '@/routes/reports';
import { index as salesIndex } from '@/routes/sales';
import { index as stationsIndex } from '@/routes/stations';
import { index as teamIndex } from '@/routes/team';
import type { NavItem } from '@/types';

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
    const { t } = useLocale();

    const mainNavItems: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.sales'),
            href: salesIndex(),
            icon: Receipt,
        },
        {
            title: t('nav.fuel_management'),
            href: fuelTanksIndex(),
            icon: Fuel,
        },
        {
            title: t('nav.cash_flow'),
            href: cashFlowIndex(),
            icon: Wallet,
        },
        {
            title: t('nav.sales_analysis'),
            href: analyticsIndex(),
            icon: LineChart,
        },
        {
            title: t('nav.reports'),
            href: reportsIndex(),
            icon: BookOpen,
        },
        {
            title: t('nav.stations'),
            href: stationsIndex(),
            icon: MapPin,
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
        },
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
                <StationSwitcher />
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <div className="px-2 pb-1">
                    <LanguageSwitcher />
                </div>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
