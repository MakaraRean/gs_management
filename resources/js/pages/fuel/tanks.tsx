import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import InputError from '@/components/input-error';
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
import { formatVolume } from '@/lib/format';
import { destroy, store, update } from '@/routes/fuel/tanks';

type Tank = {
    id: number;
    name: string;
    fuel_type_id: number;
    capacity: string;
    current_volume: string;
    fill_percentage: number;
    pumps_count: number;
    fuel_type: { id: number; name: string; unit: string } | null;
};

type FuelTypeOption = { id: number; name: string; unit: string };

type TanksProps = {
    tanks: Tank[];
    fuelTypes: FuelTypeOption[];
};

function barColor(fill: number): string {
    if (fill <= 15) {
        return 'bg-red-500';
    }

    if (fill <= 30) {
        return 'bg-amber-500';
    }

    return 'bg-emerald-500';
}

export default function Tanks({ tanks, fuelTypes }: TanksProps) {
    const { t } = useLocale();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Tank | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        fuel_type_id: '',
        capacity: '',
        current_volume: '',
    });

    const openCreate = () => {
        reset();
        setEditing(null);
        setOpen(true);
    };

    const openEdit = (tank: Tank) => {
        setEditing(tank);
        setData({
            name: tank.name,
            fuel_type_id: String(tank.fuel_type_id),
            capacity: tank.capacity,
            current_volume: tank.current_volume,
        });
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { onSuccess: () => setOpen(false) };

        if (editing) {
            put(update({ tank: editing.id }).url, options);
        } else {
            post(store().url, options);
        }
    };

    const remove = (tank: Tank) => {
        if (confirm(`${t('common.delete')} ${tank.name}?`)) {
            router.delete(destroy({ tank: tank.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={t('fuel.tanks_title')} />

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">{t('fuel.tanks_title')}</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        {t('common.add')}
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {tanks.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            {t('fuel.no_tanks')}
                        </p>
                    )}
                    {tanks.map((tank) => (
                        <Card key={tank.id}>
                            <CardContent className="space-y-3 pt-6">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {tank.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {tank.fuel_type?.name ?? '—'} ·{' '}
                                            {tank.pumps_count === 1
                                                ? t('fuel.pumps_count_one')
                                                : t('fuel.pumps_count_other', { count: String(tank.pumps_count) })}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => openEdit(tank)}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="text-destructive"
                                            onClick={() => remove(tank)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${barColor(
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
                                            tank.fuel_type?.unit ?? 'L',
                                        )}{' '}
                                        /{' '}
                                        {formatVolume(
                                            tank.capacity,
                                            tank.fuel_type?.unit ?? 'L',
                                        )}
                                    </span>
                                    {tank.fill_percentage <= 15 ? (
                                        <Badge variant="destructive">{t('common.low')}</Badge>
                                    ) : (
                                        <span className="tabular-nums">
                                            {tank.fill_percentage}%
                                        </span>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? t('fuel.edit_tank') : t('fuel.new_tank')}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('common.name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="e.g. Tank A"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="fuel_type_id">{t('fuel.fuel_type')}</Label>
                            <Select
                                value={data.fuel_type_id}
                                onValueChange={(value) =>
                                    setData('fuel_type_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="fuel_type_id"
                                    className="w-full"
                                >
                                    <SelectValue placeholder={t('common.select_fuel_type')} />
                                </SelectTrigger>
                                <SelectContent>
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
                            <InputError message={errors.fuel_type_id} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="capacity">{t('fuel.capacity')}</Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.capacity}
                                    onChange={(event) =>
                                        setData('capacity', event.target.value)
                                    }
                                />
                                <InputError message={errors.capacity} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="current_volume">
                                    {t('fuel.current_volume')}
                                </Label>
                                <Input
                                    id="current_volume"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.current_volume}
                                    onChange={(event) =>
                                        setData(
                                            'current_volume',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.current_volume} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                {editing ? t('common.save_changes') : t('common.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
