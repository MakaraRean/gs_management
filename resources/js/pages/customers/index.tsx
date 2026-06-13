import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { index, destroy, show, store } from '@/routes/customers';

type Customer = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    credit_limit: string;
    outstanding_balance: number;
};

type CustomersProps = {
    customers: Customer[];
    customersPerStation: boolean;
};

export default function CustomersIndex({ customers, customersPerStation }: CustomersProps) {
    const { t } = useLocale();
    const { current_station, stations } = usePage().props;
    const [open, setOpen] = useState(false);

    const multipleStations = (stations as unknown[]).length > 1;

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        phone: '',
        email: '',
        notes: '',
        credit_limit: '0',
        station_id: customersPerStation && current_station
            ? String((current_station as { id: number }).id)
            : null as string | null,
    });

    const isStationOnly = data.station_id !== null;

    const toggleStationOnly = (checked: boolean) => {
        setData('station_id', checked && current_station
            ? String((current_station as { id: number }).id)
            : null);
    };

    const openCreate = () => {
        reset();
        // Re-apply default after reset
        setData('station_id', customersPerStation && current_station
            ? String((current_station as { id: number }).id)
            : null);
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store().url, { onSuccess: () => setOpen(false) });
    };

    const remove = (customer: Customer) => {
        if (confirm(t('customers.delete_confirm'))) {
            router.delete(destroy({ customer: customer.id }).url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={t('customers.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">{t('customers.title')}</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        {t('customers.new_customer')}
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('common.name')}</TableHead>
                            <TableHead>{t('stations.phone')}</TableHead>
                            <TableHead className="text-right">{t('customers.outstanding_balance')}</TableHead>
                            <TableHead className="text-right">{t('common.actions')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {customers.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center text-muted-foreground">
                                    {t('customers.no_customers')}
                                </TableCell>
                            </TableRow>
                        )}
                        {customers.map((customer) => (
                            <TableRow key={customer.id}>
                                <TableCell>
                                    <Link
                                        href={show({ customer: customer.id }).url}
                                        className="flex items-center gap-2 font-medium hover:underline"
                                    >
                                        <Users className="h-4 w-4 text-muted-foreground" />
                                        {customer.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-muted-foreground">{customer.phone ?? '—'}</TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {customer.outstanding_balance > 0 ? (
                                        <Badge variant="destructive">
                                            {formatCurrency(customer.outstanding_balance)}
                                        </Badge>
                                    ) : (
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link href={show({ customer: customer.id }).url}>
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => remove(customer)}
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
                        <DialogTitle>{t('customers.new_customer')}</DialogTitle>
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
                        <div className="grid gap-4 sm:grid-cols-2">
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
                                <Label htmlFor="credit_limit">{t('customers.credit_limit')}</Label>
                                <Input
                                    id="credit_limit"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={data.credit_limit}
                                    onChange={(e) => setData('credit_limit', e.target.value)}
                                />
                                <InputError message={errors.credit_limit} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="notes">{t('customers.notes')}</Label>
                            <Input
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                            />
                            <InputError message={errors.notes} />
                        </div>
                        {multipleStations && current_station && (
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="station_only"
                                    checked={isStationOnly}
                                    onCheckedChange={(checked) => toggleStationOnly(!!checked)}
                                />
                                <Label htmlFor="station_only" className="cursor-pointer font-normal">
                                    {t('customers.this_station_only')}
                                    {' '}
                                    <span className="text-muted-foreground">
                                        ({(current_station as { name: string }).name})
                                    </span>
                                </Label>
                                <InputError message={errors.station_id} />
                            </div>
                        )}
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                {t('common.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'customers.title', href: index().url }],
};
