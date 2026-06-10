import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { formatCurrency, formatDate, formatVolume } from '@/lib/format';
import { destroy, store } from '@/routes/fuel/deliveries';

type Delivery = {
    id: number;
    volume: string;
    cost_per_unit: string;
    total_cost: string;
    supplier: string | null;
    delivered_at: string;
    tank: {
        name: string;
        fuel_type: { name: string; unit: string } | null;
    } | null;
};

type TankOption = {
    id: number;
    name: string;
    fuel_type: { name: string } | null;
};

type DeliveriesProps = {
    deliveries: Delivery[];
    tanks: TankOption[];
};

export default function Deliveries({ deliveries, tanks }: DeliveriesProps) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        tank_id: '',
        volume: '',
        cost_per_unit: '',
        supplier: '',
        delivered_at: '',
        notes: '',
    });

    const volume = Number.parseFloat(data.volume) || 0;
    const cost = Number.parseFloat(data.cost_per_unit) || 0;
    const total = volume * cost;

    const openCreate = () => {
        reset();
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store().url, { onSuccess: () => setOpen(false) });
    };

    const remove = (delivery: Delivery) => {
        if (confirm('Delete this delivery record?')) {
            router.delete(destroy({ delivery: delivery.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Deliveries" />

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">Deliveries</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Record delivery
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Tank</TableHead>
                            <TableHead>Supplier</TableHead>
                            <TableHead className="text-right">Volume</TableHead>
                            <TableHead className="text-right">
                                Cost / unit
                            </TableHead>
                            <TableHead className="text-right">Total</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {deliveries.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="text-center text-muted-foreground"
                                >
                                    No deliveries yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {deliveries.map((delivery) => (
                            <TableRow key={delivery.id}>
                                <TableCell className="text-muted-foreground">
                                    {formatDate(delivery.delivered_at)}
                                </TableCell>
                                <TableCell>
                                    {delivery.tank?.name ?? '—'}
                                </TableCell>
                                <TableCell>
                                    {delivery.supplier ?? '—'}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatVolume(
                                        delivery.volume,
                                        delivery.tank?.fuel_type?.unit ?? 'L',
                                    )}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatCurrency(delivery.cost_per_unit)}
                                </TableCell>
                                <TableCell className="text-right font-medium tabular-nums">
                                    {formatCurrency(delivery.total_cost)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => remove(delivery)}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record delivery</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="tank_id">Tank</Label>
                            <Select
                                value={data.tank_id}
                                onValueChange={(value) =>
                                    setData('tank_id', value)
                                }
                            >
                                <SelectTrigger id="tank_id" className="w-full">
                                    <SelectValue placeholder="Select tank" />
                                </SelectTrigger>
                                <SelectContent>
                                    {tanks.map((tank) => (
                                        <SelectItem
                                            key={tank.id}
                                            value={String(tank.id)}
                                        >
                                            {tank.name}
                                            {tank.fuel_type
                                                ? ` — ${tank.fuel_type.name}`
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tank_id} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="volume">Volume</Label>
                                <Input
                                    id="volume"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.volume}
                                    onChange={(event) =>
                                        setData('volume', event.target.value)
                                    }
                                />
                                <InputError message={errors.volume} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="cost_per_unit">
                                    Cost / unit
                                </Label>
                                <Input
                                    id="cost_per_unit"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.cost_per_unit}
                                    onChange={(event) =>
                                        setData(
                                            'cost_per_unit',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.cost_per_unit} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="supplier">
                                Supplier (optional)
                            </Label>
                            <Input
                                id="supplier"
                                value={data.supplier}
                                onChange={(event) =>
                                    setData('supplier', event.target.value)
                                }
                            />
                            <InputError message={errors.supplier} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="delivered_at">Delivered at</Label>
                            <Input
                                id="delivered_at"
                                type="datetime-local"
                                value={data.delivered_at}
                                onChange={(event) =>
                                    setData('delivered_at', event.target.value)
                                }
                            />
                            <InputError message={errors.delivered_at} />
                        </div>
                        <div className="flex items-center justify-between rounded-lg border px-4 py-3">
                            <span className="text-sm font-medium text-muted-foreground">
                                Total cost
                            </span>
                            <span className="text-xl font-bold tabular-nums">
                                {formatCurrency(total)}
                            </span>
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                Record delivery
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
