import { Head, useForm } from '@inertiajs/react';
import { Fuel } from 'lucide-react';
import InputError from '@/components/input-error';
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
import { formatCurrency, formatVolume } from '@/lib/format';
import { create, index, store } from '@/routes/sales';

type PumpOption = {
    id: number;
    name: string;
    fuel_type_id: number;
    fuel_type_name: string;
    unit: string;
    unit_price: number;
    available_volume: number;
};

type CreateSaleProps = {
    pumps: PumpOption[];
};

export default function CreateSale({ pumps }: CreateSaleProps) {
    const { data, setData, post, processing, errors } = useForm({
        pump_id: '',
        volume: '',
        payment_method: 'cash',
        sold_at: '',
        notes: '',
    });

    const selectedPump = pumps.find((pump) => String(pump.id) === data.pump_id);
    const volume = Number.parseFloat(data.volume) || 0;
    const total = selectedPump ? volume * selectedPump.unit_price : 0;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Record sale" />

            <div className="mx-auto flex w-full max-w-xl flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Fuel className="h-5 w-5" />
                            Record a Sale
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {pumps.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No active pumps available. Add a pump under Fuel
                                Management first.
                            </p>
                        ) : (
                            <form onSubmit={submit} className="space-y-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="pump_id">Pump</Label>
                                    <Select
                                        value={data.pump_id}
                                        onValueChange={(value) =>
                                            setData('pump_id', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="pump_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a pump" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {pumps.map((pump) => (
                                                <SelectItem
                                                    key={pump.id}
                                                    value={String(pump.id)}
                                                >
                                                    {pump.name} —{' '}
                                                    {pump.fuel_type_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.pump_id} />
                                </div>

                                {selectedPump && (
                                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-muted px-3 py-2 text-sm">
                                        <span className="text-muted-foreground">
                                            {selectedPump.fuel_type_name} @{' '}
                                            {formatCurrency(
                                                selectedPump.unit_price,
                                            )}
                                            /{selectedPump.unit}
                                        </span>
                                        <span className="text-muted-foreground">
                                            Available:{' '}
                                            {formatVolume(
                                                selectedPump.available_volume,
                                                selectedPump.unit,
                                            )}
                                        </span>
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="volume">
                                        Volume{' '}
                                        {selectedPump
                                            ? `(${selectedPump.unit})`
                                            : ''}
                                    </Label>
                                    <Input
                                        id="volume"
                                        type="number"
                                        inputMode="decimal"
                                        step="0.01"
                                        min="0"
                                        value={data.volume}
                                        onChange={(event) =>
                                            setData(
                                                'volume',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="0.00"
                                        className="h-12 text-lg"
                                    />
                                    <InputError message={errors.volume} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="payment_method">
                                        Payment method
                                    </Label>
                                    <Select
                                        value={data.payment_method}
                                        onValueChange={(value) =>
                                            setData('payment_method', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="payment_method"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="cash">
                                                Cash
                                            </SelectItem>
                                            <SelectItem value="card">
                                                Card
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.payment_method}
                                    />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border px-4 py-3">
                                    <span className="text-sm font-medium text-muted-foreground">
                                        Total
                                    </span>
                                    <span className="text-2xl font-bold tabular-nums">
                                        {formatCurrency(total)}
                                    </span>
                                </div>

                                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <a href={index().url}>Cancel</a>
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-11"
                                    >
                                        Record sale
                                    </Button>
                                </div>
                            </form>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateSale.layout = {
    breadcrumbs: [
        { title: 'Sales', href: index() },
        { title: 'Record sale', href: create() },
    ],
};
