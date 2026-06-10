import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { formatCurrency, formatDateTime, formatVolume } from '@/lib/format';
import { create, destroy, index } from '@/routes/sales';

type SaleRow = {
    id: number;
    volume: string;
    unit_price: string;
    total_amount: string;
    payment_method: string;
    sold_at: string;
    pump: { name: string } | null;
    fuel_type: { name: string; unit: string } | null;
    user: { name: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};

type Filters = {
    from: string | null;
    to: string | null;
    fuel_type_id: number | null;
};

type SalesIndexProps = {
    sales: Paginated<SaleRow>;
    filters: Filters;
    fuelTypes: { id: number; name: string }[];
};

export default function SalesIndex({
    sales,
    filters,
    fuelTypes,
}: SalesIndexProps) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [fuelTypeId, setFuelTypeId] = useState(
        filters.fuel_type_id ? String(filters.fuel_type_id) : 'all',
    );

    const applyFilters = () => {
        router.get(
            index().url,
            {
                from: from || undefined,
                to: to || undefined,
                fuel_type_id: fuelTypeId === 'all' ? undefined : fuelTypeId,
            },
            { preserveState: true, replace: true },
        );
    };

    const removeSale = (id: number) => {
        if (confirm('Delete this sale? Tank volume will not be restored.')) {
            router.delete(destroy({ sale: id }).url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Sales" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Sales</h1>
                        <p className="text-sm text-muted-foreground">
                            {sales.total} total transaction
                            {sales.total === 1 ? '' : 's'}
                        </p>
                    </div>
                    <Button asChild className="w-full sm:w-auto">
                        <Link href={create()}>
                            <Plus className="h-4 w-4" />
                            Record sale
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="flex flex-col gap-3 pt-6 md:flex-row md:items-end">
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="from">From</Label>
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
                            <Label htmlFor="to">To</Label>
                            <Input
                                id="to"
                                type="date"
                                value={to}
                                onChange={(event) => setTo(event.target.value)}
                            />
                        </div>
                        <div className="grid flex-1 gap-1.5">
                            <Label htmlFor="fuel_type">Fuel type</Label>
                            <Select
                                value={fuelTypeId}
                                onValueChange={setFuelTypeId}
                            >
                                <SelectTrigger
                                    id="fuel_type"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    {fuelTypes.map((fuelType) => (
                                        <SelectItem
                                            key={fuelType.id}
                                            value={String(fuelType.id)}
                                        >
                                            {fuelType.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            onClick={applyFilters}
                            className="w-full md:w-auto"
                        >
                            Apply
                        </Button>
                    </CardContent>
                </Card>

                {/* Mobile: stacked cards */}
                <div className="grid gap-3 md:hidden">
                    {sales.data.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No sales found.
                        </p>
                    )}
                    {sales.data.map((sale) => (
                        <Card key={sale.id}>
                            <CardContent className="space-y-2 pt-6">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-semibold">
                                        {sale.fuel_type?.name ?? '—'}
                                    </span>
                                    <span className="font-bold tabular-nums">
                                        {formatCurrency(sale.total_amount)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-sm text-muted-foreground">
                                    <span>{sale.pump?.name ?? '—'}</span>
                                    <span className="tabular-nums">
                                        {formatVolume(
                                            sale.volume,
                                            sale.fuel_type?.unit ?? 'L',
                                        )}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{formatDateTime(sale.sold_at)}</span>
                                    <Badge variant="secondary">
                                        {sale.payment_method}
                                    </Badge>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive"
                                    onClick={() => removeSale(sale.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    Delete
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Desktop: table */}
                <Card className="hidden md:block">
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Pump</TableHead>
                                    <TableHead>Fuel</TableHead>
                                    <TableHead className="text-right">
                                        Volume
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit price
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sales.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="text-center text-muted-foreground"
                                        >
                                            No sales found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {sales.data.map((sale) => (
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
                                        <TableCell className="text-right tabular-nums">
                                            {formatCurrency(sale.unit_price)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium tabular-nums">
                                            {formatCurrency(sale.total_amount)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {sale.payment_method}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive"
                                                onClick={() =>
                                                    removeSale(sale.id)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {sales.links.length > 3 && (
                    <div className="flex flex-wrap items-center justify-center gap-1">
                        {sales.links.map((link, linkIndex) =>
                            link.url ? (
                                <Button
                                    key={linkIndex}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={linkIndex}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

SalesIndex.layout = {
    breadcrumbs: [{ title: 'Sales', href: index() }],
};
