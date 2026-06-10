import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency } from '@/lib/format';
import { destroy, store, update } from '@/routes/fuel/types';

type FuelType = {
    id: number;
    name: string;
    unit_price: string;
    unit: string;
    color: string;
    tanks_count: number;
};

type FuelTypesProps = {
    fuelTypes: FuelType[];
};

export default function FuelTypes({ fuelTypes }: FuelTypesProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<FuelType | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        unit_price: '',
        unit: 'L',
        color: '#2563eb',
    });

    const openCreate = () => {
        reset();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (fuelType: FuelType) => {
        setEditing(fuelType);
        setData({
            name: fuelType.name,
            unit_price: fuelType.unit_price,
            unit: fuelType.unit,
            color: fuelType.color,
        });
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { onSuccess: () => setOpen(false) };

        if (editing) {
            put(update({ fuelType: editing.id }).url, options);
        } else {
            post(store().url, options);
        }
    };

    const remove = (fuelType: FuelType) => {
        if (confirm(`Delete ${fuelType.name}?`)) {
            router.delete(destroy({ fuelType: fuelType.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Fuel types" />

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">Fuel Types</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Add
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead className="text-right">
                                Price / unit
                            </TableHead>
                            <TableHead className="text-right">Tanks</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {fuelTypes.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="text-center text-muted-foreground"
                                >
                                    No fuel types yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {fuelTypes.map((fuelType) => (
                            <TableRow key={fuelType.id}>
                                <TableCell>
                                    <span className="flex items-center gap-2 font-medium">
                                        <span
                                            className="inline-block h-3 w-3 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor: fuelType.color,
                                            }}
                                        />
                                        {fuelType.name}
                                    </span>
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatCurrency(fuelType.unit_price)} /{' '}
                                    {fuelType.unit}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {fuelType.tanks_count}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => openEdit(fuelType)}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => remove(fuelType)}
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
                        <DialogTitle>
                            {editing ? 'Edit fuel type' : 'New fuel type'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="e.g. Super 95"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="unit_price">Price / unit</Label>
                                <Input
                                    id="unit_price"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.unit_price}
                                    onChange={(event) =>
                                        setData(
                                            'unit_price',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="0.00"
                                />
                                <InputError message={errors.unit_price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="unit">Unit</Label>
                                <Input
                                    id="unit"
                                    value={data.unit}
                                    onChange={(event) =>
                                        setData('unit', event.target.value)
                                    }
                                    placeholder="L"
                                />
                                <InputError message={errors.unit} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="color">Color</Label>
                            <Input
                                id="color"
                                type="color"
                                value={data.color}
                                onChange={(event) =>
                                    setData('color', event.target.value)
                                }
                                className="h-10 w-20 p-1"
                            />
                            <InputError message={errors.color} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                {editing ? 'Save changes' : 'Create'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
