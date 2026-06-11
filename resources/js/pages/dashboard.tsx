import { Head } from '@inertiajs/react';
import { DollarSign, Droplets, Receipt, Wallet } from 'lucide-react';
import { useLocale } from '@/hooks/use-locale';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency, formatDateTime, formatVolume } from '@/lib/format';
import { dashboard } from '@/routes';

type Stats = {
    today_revenue: number;
    today_volume: number;
    today_transactions: number;
    net_cash: number;
};

type TankLevel = {
    id: number;
    name: string;
    fuel_type: string;
    unit: string;
    capacity: number;
    current_volume: number;
    fill_percentage: number;
};

type RecentSale = {
    id: number;
    volume: string;
    total_amount: string;
    sold_at: string;
    payment_method: string;
    pump: { name: string } | null;
    fuel_type: { name: string; unit: string } | null;
};

type DashboardProps = {
    stats: Stats;
    tanks: TankLevel[];
    recentSales: RecentSale[];
    revenueByDay: { day: string; revenue: number }[];
};

function tankBarColor(fill: number): string {
    if (fill <= 15) {
        return 'bg-red-500';
    }

    if (fill <= 30) {
        return 'bg-amber-500';
    }

    return 'bg-emerald-500';
}

export default function Dashboard({
    stats,
    tanks,
    recentSales,
    revenueByDay,
}: DashboardProps) {
    const { t } = useLocale();

    return (
        <>
            <Head title={t('dashboard.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title={t('dashboard.today_revenue')}
                        value={formatCurrency(stats.today_revenue)}
                        icon={DollarSign}
                    />
                    <StatCard
                        title={t('dashboard.today_volume')}
                        value={formatVolume(stats.today_volume)}
                        icon={Droplets}
                    />
                    <StatCard
                        title={t('dashboard.transactions_today')}
                        value={String(stats.today_transactions)}
                        icon={Receipt}
                    />
                    <StatCard
                        title={t('dashboard.net_cash')}
                        value={formatCurrency(stats.net_cash)}
                        icon={Wallet}
                        hint={t('dashboard.net_cash_hint')}
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>{t('dashboard.revenue_last_7_days')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart
                                        data={revenueByDay}
                                        margin={{
                                            top: 8,
                                            right: 8,
                                            left: -16,
                                            bottom: 0,
                                        }}
                                    >
                                        <defs>
                                            <linearGradient
                                                id="revenueFill"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="5%"
                                                    stopColor="var(--primary)"
                                                    stopOpacity={0.4}
                                                />
                                                <stop
                                                    offset="95%"
                                                    stopColor="var(--primary)"
                                                    stopOpacity={0}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            className="stroke-muted"
                                        />
                                        <XAxis
                                            dataKey="day"
                                            tickFormatter={(value) =>
                                                String(value).slice(5)
                                            }
                                            fontSize={12}
                                            tickMargin={8}
                                        />
                                        <YAxis
                                            fontSize={12}
                                            width={48}
                                            tickFormatter={(value) =>
                                                `$${value}`
                                            }
                                        />
                                        <Tooltip
                                            formatter={(value) =>
                                                formatCurrency(value as number)
                                            }
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="revenue"
                                            stroke="var(--primary)"
                                            fill="url(#revenueFill)"
                                            strokeWidth={2}
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('dashboard.tank_levels')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {tanks.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    {t('dashboard.no_tanks')}
                                </p>
                            )}
                            {tanks.map((tank) => (
                                <div key={tank.id} className="space-y-1.5">
                                    <div className="flex items-center justify-between gap-2 text-sm">
                                        <span className="truncate font-medium">
                                            {tank.name}
                                            <span className="ml-1 text-muted-foreground">
                                                ({tank.fuel_type})
                                            </span>
                                        </span>
                                        <span className="shrink-0 text-muted-foreground tabular-nums">
                                            {tank.fill_percentage}%
                                        </span>
                                    </div>
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            className={`h-full rounded-full ${tankBarColor(
                                                tank.fill_percentage,
                                            )}`}
                                            style={{
                                                width: `${Math.min(
                                                    100,
                                                    tank.fill_percentage,
                                                )}%`,
                                            }}
                                        />
                                    </div>
                                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                                        <span className="tabular-nums">
                                            {formatVolume(
                                                tank.current_volume,
                                                tank.unit,
                                            )}{' '}
                                            /{' '}
                                            {formatVolume(
                                                tank.capacity,
                                                tank.unit,
                                            )}
                                        </span>
                                        {tank.fill_percentage <= 15 && (
                                            <Badge variant="destructive">
                                                {t('common.low')}
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('dashboard.recent_sales')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentSales.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('dashboard.no_sales')}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('common.when')}</TableHead>
                                        <TableHead>{t('common.pump')}</TableHead>
                                        <TableHead>{t('common.fuel')}</TableHead>
                                        <TableHead className="text-right">
                                            {t('common.volume')}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            {t('common.amount')}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentSales.map((sale) => (
                                        <TableRow key={sale.id}>
                                            <TableCell className="text-muted-foreground">
                                                {formatDateTime(sale.sold_at)}
                                            </TableCell>
                                            <TableCell>
                                                {sale.pump?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {sale.fuel_type?.name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatVolume(
                                                    sale.volume,
                                                    sale.fuel_type?.unit ?? 'L',
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-medium tabular-nums">
                                                {formatCurrency(
                                                    sale.total_amount,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'dashboard.title',
            href: dashboard(),
        },
    ],
};
