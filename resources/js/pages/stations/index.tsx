import { Head, router, useForm } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import { CurrencySelect } from '@/components/currency-select';
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
import { useCurrency } from '@/hooks/use-currency';
import { index, destroy, store, update } from '@/routes/stations';

type Station = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    // Null means the station uses its business's currency.
    currency_id: number | null;
    tanks_count: number;
};

type StationsProps = {
    stations: Station[];
    business_currency_id: number;
};

export default function StationsIndex({ stations, business_currency_id }: StationsProps) {
    const { t } = useLocale();
    const { currencies } = useCurrency();
    const businessCurrency = currencies.find((currency) => currency.id === business_currency_id);
    const currencyCode = (id: number) => currencies.find((currency) => currency.id === id)?.code ?? '';
    const [open, setOpen] = useState(
        () =>
            typeof window !== 'undefined' &&
            new URLSearchParams(window.location.search).has('new'),
    );
    const [editing, setEditing] = useState<Station | null>(null);

    const blankStation = {
        name: '',
        phone: '',
        email: '',
        address: '',
        currency_id: '',
    };

    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm(blankStation);

    const openCreate = () => {
        setEditing(null);
        setData(blankStation);
        clearErrors();
        setOpen(true);
    };

    const openEdit = (station: Station) => {
        setEditing(station);
        setData({
            name: station.name,
            phone: station.phone ?? '',
            email: station.email ?? '',
            address: station.address ?? '',
            currency_id: station.currency_id === null ? '' : String(station.currency_id),
        });
        clearErrors();
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { onSuccess: () => setOpen(false) };
        if (editing) {
            const effectiveCurrencyId = (id: number | null) => id ?? business_currency_id;
            const chosenCurrencyId = data.currency_id === '' ? null : Number(data.currency_id);

            if (
                effectiveCurrencyId(chosenCurrencyId) !== effectiveCurrencyId(editing.currency_id) &&
                !confirm(t('currencies.change_confirm'))
            ) {
                return;
            }

            put(update({ station: editing.id }).url, options);
        } else {
            post(store().url, options);
        }
    };

    const remove = (station: Station) => {
        if (confirm(t('stations.delete_confirm'))) {
            router.delete(destroy({ station: station.id }).url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={t('stations.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">{t('stations.title')}</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        {t('stations.new_station')}
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('common.name')}</TableHead>
                            <TableHead>{t('stations.phone')}</TableHead>
                            <TableHead>{t('stations.address')}</TableHead>
                            <TableHead>{t('stations.currency')}</TableHead>
                            <TableHead className="text-right">{t('common.actions')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {stations.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center text-muted-foreground">
                                    {t('stations.no_stations')}
                                </TableCell>
                            </TableRow>
                        )}
                        {stations.map((station) => (
                            <TableRow key={station.id}>
                                <TableCell>
                                    <span className="flex items-center gap-2 font-medium">
                                        <MapPin className="h-4 w-4 text-muted-foreground" />
                                        {station.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {station.tanks_count === 1
                                            ? t('stations.tanks_count_one')
                                            : t('stations.tanks_count_other').replace(':count', String(station.tanks_count))}
                                    </span>
                                </TableCell>
                                <TableCell className="text-muted-foreground">{station.phone ?? '—'}</TableCell>
                                <TableCell className="max-w-xs truncate text-muted-foreground">
                                    {station.address ?? '—'}
                                </TableCell>
                                <TableCell>
                                    <span className="font-medium">
                                        {currencyCode(station.currency_id ?? business_currency_id)}
                                    </span>
                                    {station.currency_id === null && (
                                        <span className="ml-1 text-xs text-muted-foreground">
                                            ({t('stations.currency_inherited')})
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" onClick={() => openEdit(station)}>
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => remove(station)}
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
                            {editing ? t('stations.edit_station') : t('stations.new_station')}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('common.name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">{t('stations.phone')}</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                            />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="address">{t('stations.address')}</Label>
                            <Input
                                id="address"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="currency_id">{t('stations.currency')}</Label>
                            <CurrencySelect
                                id="currency_id"
                                value={data.currency_id}
                                onValueChange={(value) => setData('currency_id', value)}
                                inheritLabel={t('stations.inherit_currency', {
                                    currency: businessCurrency?.code ?? '',
                                })}
                            />
                            <InputError message={errors.currency_id} />
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

StationsIndex.layout = {
    breadcrumbs: [{ title: 'stations.title', href: index().url }],
};
