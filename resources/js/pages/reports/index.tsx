import { Head, router } from '@inertiajs/react';
import { Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { index } from '@/routes/reports';

type Column = { key: string; label: string };

type Report = {
    title: string;
    columns: Column[];
    rows: Record<string, string | number>[];
    totals: Record<string, string | number> | null;
};

type ReportsProps = {
    type: string;
    filters: { from: string; to: string };
    report: Report;
};

function downloadCsv(report: Report): void {
    const header = report.columns.map((column) => column.label);
    const lines = report.rows.map((row) =>
        report.columns
            .map((column) => {
                const value = row[column.key] ?? '';
                const text = String(value).replace(/"/g, '""');

                return `"${text}"`;
            })
            .join(','),
    );
    const csv = [header.join(','), ...lines].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${report.title.toLowerCase().replace(/\s+/g, '-')}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

export default function Reports({ type, filters, report }: ReportsProps) {
    const { t } = useLocale();
    const [reportType, setReportType] = useState(type);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const REPORT_TYPES = [
        { value: 'daily_sales', label: t('reports.daily_sales') },
        { value: 'inventory', label: t('reports.fuel_inventory') },
        { value: 'cash_flow', label: t('reports.cash_flow_statement') },
    ];

    const generate = () => {
        router.get(
            index().url,
            { type: reportType, from, to },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title={t('reports.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">{t('reports.title')}</h1>

                <Card className="print:hidden">
                    <CardContent className="flex flex-col gap-3 pt-6 lg:flex-row lg:items-end">
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="type">{t('reports.report')}</Label>
                            <Select
                                value={reportType}
                                onValueChange={setReportType}
                            >
                                <SelectTrigger id="type" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {REPORT_TYPES.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="from">{t('common.from')}</Label>
                            <Input
                                id="from"
                                type="date"
                                value={from}
                                onChange={(event) =>
                                    setFrom(event.target.value)
                                }
                                disabled={reportType === 'inventory'}
                            />
                        </div>
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="to">{t('common.to')}</Label>
                            <Input
                                id="to"
                                type="date"
                                value={to}
                                onChange={(event) => setTo(event.target.value)}
                                disabled={reportType === 'inventory'}
                            />
                        </div>
                        <Button onClick={generate} className="w-full lg:w-auto">
                            {t('reports.generate')}
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle>{report.title}</CardTitle>
                        <div className="flex flex-wrap gap-2 print:hidden">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => downloadCsv(report)}
                            >
                                <Download className="h-4 w-4" />
                                CSV
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => window.print()}
                            >
                                <Printer className="h-4 w-4" />
                                {t('reports.print')}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {report.columns.map((column) => (
                                        <TableHead key={column.key}>
                                            {column.label}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {report.rows.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={report.columns.length}
                                            className="text-center text-muted-foreground"
                                        >
                                            {t('reports.no_data')}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {report.rows.map((row, rowIndex) => (
                                    <TableRow key={rowIndex}>
                                        {report.columns.map((column) => (
                                            <TableCell key={column.key}>
                                                {row[column.key]}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {report.totals && (
                            <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t pt-4 text-sm">
                                {Object.entries(report.totals).map(
                                    ([key, value]) => (
                                        <div
                                            key={key}
                                            className="flex items-center gap-2"
                                        >
                                            <span className="text-muted-foreground capitalize">
                                                {key.replace(/_/g, ' ')}:
                                            </span>
                                            <span className="font-semibold tabular-nums">
                                                {value}
                                            </span>
                                        </div>
                                    ),
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Reports.layout = {
    breadcrumbs: [{ title: 'reports.title', href: index() }],
};
