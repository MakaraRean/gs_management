import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { destroy, store, update } from '@/routes/fuel/pumps';

type Pump = {
    id: number;
    name: string;
    tank_id: number;
    status: string;
    tank: {
        name: string;
        fuel_type: { name: string } | null;
    } | null;
};

type TankOption = {
    id: number;
    name: string;
    fuel_type: { name: string } | null;
};

type PumpsProps = {
    pumps: Pump[];
    tanks: TankOption[];
};

export default function Pumps({ pumps, tanks }: PumpsProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Pump | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        tank_id: '',
        status: 'active',
    });

    const openCreate = () => {
        reset();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (pump: Pump) => {
        setEditing(pump);
        setData({
            name: pump.name,
            tank_id: String(pump.tank_id),
            status: pump.status,
        });
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { onSuccess: () => setOpen(false) };

        if (editing) {
            put(update({ pump: editing.id }).url, options);
        } else {
            post(store().url, options);
        }
    };

    const remove = (pump: Pump) => {
        if (confirm(`Delete ${pump.name}?`)) {
            router.delete(destroy({ pump: pump.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Pumps" />

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">Pumps</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Add
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Tank</TableHead>
                            <TableHead>Fuel</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {pumps.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No pumps yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {pumps.map((pump) => (
                            <TableRow key={pump.id}>
                                <TableCell className="font-medium">
                                    {pump.name}
                                </TableCell>
                                <TableCell>{pump.tank?.name ?? '—'}</TableCell>
                                <TableCell>
                                    {pump.tank?.fuel_type?.name ?? '—'}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            pump.status === 'active'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {pump.status}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => openEdit(pump)}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => remove(pump)}
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
                            {editing ? 'Edit pump' : 'New pump'}
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
                                placeholder="e.g. Pump 1"
                            />
                            <InputError message={errors.name} />
                        </div>
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
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) =>
                                    setData('status', value)
                                }
                            >
                                <SelectTrigger id="status" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
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
