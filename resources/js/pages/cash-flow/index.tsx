import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Plus,
    Trash2,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import InputError from '@/components/input-error';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatCurrency, formatDate } from '@/lib/format';
import { destroy, index, store } from '@/routes/cash-flow';

type LedgerEntry = {
    id: number | string;
    date: string;
    direction: 'in' | 'out';
    category: string;
    description: string;
    amount: number;
    is_manual: boolean;
};

type CashFlowProps = {
    entries: LedgerEntry[];
    summary: { total_in: number; total_out: number; net: number };
    filters: { from: string; to: string };
};

export default function CashFlow({ entries, summary, filters }: CashFlowProps) {
    const { t } = useLocale();
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        direction: 'out',
        category: 'expense',
        amount: '',
        description: '',
        occurred_at: '',
    });

    const CATEGORY_LABELS: Record<string, string> = {
        sale: t('cash_flow.category_sale'),
        fuel_purchase: t('cash_flow.category_fuel_purchase'),
        expense: t('cash_flow.category_expense'),
        other_income: t('cash_flow.category_other_income'),
        withdrawal: t('cash_flow.category_withdrawal'),
        deposit: t('cash_flow.category_deposit'),
    };

    const applyFilters = () => {
        router.get(
            index().url,
            { from, to },
            { preserveState: true, replace: true },
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store().url, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const remove = (entry: LedgerEntry) => {
        if (entry.is_manual && confirm(t('cash_flow.delete_confirm'))) {
            router.delete(destroy({ cashFlow: Number(entry.id) }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={t('cash_flow.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-xl font-semibold">{t('cash_flow.title')}</h1>
                    <Button
                        onClick={() => setOpen(true)}
                        className="w-full sm:w-auto"
                    >
                        <Plus className="h-4 w-4" />
                        {t('cash_flow.add_entry')}
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <StatCard
                        title={t('cash_flow.cash_in')}
                        value={formatCurrency(summary.total_in)}
                        icon={ArrowUpCircle}
                    />
                    <StatCard
                        title={t('cash_flow.cash_out')}
                        value={formatCurrency(summary.total_out)}
                        icon={ArrowDownCircle}
                    />
                    <StatCard
                        title={t('cash_flow.net')}
                        value={formatCurrency(summary.net)}
                        icon={Wallet}
                    />
                </div>

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

                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('common.date')}</TableHead>
                                    <TableHead>{t('common.description')}</TableHead>
                                    <TableHead>{t('cash_flow.category')}</TableHead>
                                    <TableHead className="text-right">
                                        {t('common.amount')}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t('common.actions')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entries.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            {t('cash_flow.no_entries')}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {entries.map((entry) => (
                                    <TableRow key={entry.id}>
                                        <TableCell className="text-muted-foreground">
                                            {formatDate(entry.date)}
                                        </TableCell>
                                        <TableCell>
                                            {entry.description}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {CATEGORY_LABELS[
                                                    entry.category
                                                ] ?? entry.category}
                                            </Badge>
                                        </TableCell>
                                        <TableCell
                                            className={`text-right font-medium tabular-nums ${
                                                entry.direction === 'in'
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-red-600 dark:text-red-400'
                                            }`}
                                        >
                                            {entry.direction === 'in'
                                                ? '+'
                                                : '−'}
                                            {formatCurrency(entry.amount)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {entry.is_manual ? (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive"
                                                    onClick={() =>
                                                        remove(entry)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    {t('common.auto')}
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('cash_flow.add_entry')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="direction">{t('cash_flow.direction')}</Label>
                                <Select
                                    value={data.direction}
                                    onValueChange={(value) =>
                                        setData('direction', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="direction"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="in">
                                            {t('cash_flow.cash_in')}
                                        </SelectItem>
                                        <SelectItem value="out">
                                            {t('cash_flow.cash_out')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.direction} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category">{t('cash_flow.category')}</Label>
                                <Select
                                    value={data.category}
                                    onValueChange={(value) =>
                                        setData('category', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="expense">
                                            {t('cash_flow.category_expense')}
                                        </SelectItem>
                                        <SelectItem value="other_income">
                                            {t('cash_flow.category_other_income')}
                                        </SelectItem>
                                        <SelectItem value="withdrawal">
                                            {t('cash_flow.category_withdrawal')}
                                        </SelectItem>
                                        <SelectItem value="deposit">
                                            {t('cash_flow.category_deposit')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.category} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="amount">{t('common.amount')}</Label>
                            <Input
                                id="amount"
                                type="number"
                                inputMode="decimal"
                                step="0.01"
                                min="0"
                                value={data.amount}
                                onChange={(event) =>
                                    setData('amount', event.target.value)
                                }
                            />
                            <InputError message={errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">{t('common.description')}</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />
                            <InputError message={errors.description} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="occurred_at">{t('common.date')}</Label>
                            <Input
                                id="occurred_at"
                                type="date"
                                value={data.occurred_at}
                                onChange={(event) =>
                                    setData('occurred_at', event.target.value)
                                }
                            />
                            <InputError message={errors.occurred_at} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                {t('common.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

CashFlow.layout = {
    breadcrumbs: [{ title: 'cash_flow.title', href: index() }],
};
