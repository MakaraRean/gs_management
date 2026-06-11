import { Head, router } from '@inertiajs/react';
import { DollarSign, Droplets, Receipt, TrendingUp } from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { useLocale } from '@/hooks/use-locale';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatNumber, formatVolume } from '@/lib/format';
import { index } from '@/routes/analytics';

type AnalyticsProps = {
    filters: { from: string; to: string };
    revenueByDay: {
        day: string;
        revenue: number;
        volume: number;
        transactions: number;
    }[];
    byFuelType: {
        name: string;
        color: string;
        revenue: number;
        volume: number;
    }[];
    byPump: { name: string; revenue: number }[];
    byPayment: { name: string; revenue: number }[];
    totals: {
        revenue: number;
        volume: number;
        transactions: number;
        average_ticket: number;
    };
};

const PAYMENT_COLORS = ['#2563eb', '#16a34a', '#f59e0b', '#db2777'];

export default function Analytics({
    filters,
    revenueByDay,
    byFuelType,
    byPump,
    byPayment,
    totals,
}: AnalyticsProps) {
    const { t } = useLocale();
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const applyFilters = () => {
        router.get(
            index().url,
            { from, to },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title={t('analytics.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-end">
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="from">{t('common.from')}</Label>
                            <Input
                                id="from"
                                type="date"
                                value={from}
                                onChange={(event) =>
                                    setFrom(event.target.value)
                                }
                            />
                        </div>
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="to">{t('common.to')}</Label>
                            <Input
                                id="to"
                                type="date"
                                value={to}
                                onChange={(event) => setTo(event.target.value)}
                            />
                        </div>
                        <Button
                            onClick={applyFilters}
                            className="w-full sm:w-auto"
                        >
                            {t('common.apply')}
                        </Button>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title={t('analytics.revenue')}
                        value={formatCurrency(totals.revenue)}
                        icon={DollarSign}
                    />
                    <StatCard
                        title={t('analytics.volume')}
                        value={formatVolume(totals.volume)}
                        icon={Droplets}
                    />
                    <StatCard
                        title={t('analytics.transactions')}
                        value={formatNumber(totals.transactions)}
                        icon={Receipt}
                    />
                    <StatCard
                        title={t('analytics.avg_ticket')}
                        value={formatCurrency(totals.average_ticket)}
                        icon={TrendingUp}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('analytics.revenue_volume_over_time')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-72 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart
                                    data={revenueByDay}
                                    margin={{
                                        top: 8,
                                        right: 8,
                                        left: -16,
                                        bottom: 0,
                                    }}
                                >
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
                                    <YAxis fontSize={12} width={48} />
                                    <Tooltip
                                        formatter={(value, name) =>
                                            name === 'revenue'
                                                ? formatCurrency(
                                                      value as number,
                                                  )
                                                : formatVolume(value as number)
                                        }
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="revenue"
                                        stroke="#2563eb"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="volume"
                                        stroke="#16a34a"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('analytics.revenue_by_fuel_type')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={byFuelType}
                                        margin={{
                                            top: 8,
                                            right: 8,
                                            left: -16,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            className="stroke-muted"
                                        />
                                        <XAxis
                                            dataKey="name"
                                            fontSize={12}
                                            tickMargin={8}
                                        />
                                        <YAxis fontSize={12} width={48} />
                                        <Tooltip
                                            formatter={(value) =>
                                                formatCurrency(value as number)
                                            }
                                        />
                                        <Bar
                                            dataKey="revenue"
                                            radius={[4, 4, 0, 0]}
                                        >
                                            {byFuelType.map((entry) => (
                                                <Cell
                                                    key={entry.name}
                                                    fill={entry.color}
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('analytics.revenue_by_payment')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={byPayment}
                                            dataKey="revenue"
                                            nameKey="name"
                                            innerRadius="50%"
                                            outerRadius="80%"
                                        >
                                            {byPayment.map(
                                                (entry, entryIndex) => (
                                                    <Cell
                                                        key={entry.name}
                                                        fill={
                                                            PAYMENT_COLORS[
                                                                entryIndex %
                                                                    PAYMENT_COLORS.length
                                                            ]
                                                        }
                                                    />
                                                ),
                                            )}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value) =>
                                                formatCurrency(value as number)
                                            }
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('analytics.revenue_by_pump')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-64 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={byPump}
                                    layout="vertical"
                                    margin={{
                                        top: 8,
                                        right: 8,
                                        left: 8,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-muted"
                                    />
                                    <XAxis type="number" fontSize={12} />
                                    <YAxis
                                        type="category"
                                        dataKey="name"
                                        fontSize={12}
                                        width={96}
                                    />
                                    <Tooltip
                                        formatter={(value) =>
                                            formatCurrency(value as number)
                                        }
                                    />
                                    <Bar
                                        dataKey="revenue"
                                        fill="#2563eb"
                                        radius={[0, 4, 4, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Analytics.layout = {
    breadcrumbs: [{ title: 'analytics.title', href: index() }],
};
