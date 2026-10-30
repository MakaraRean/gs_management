import { Head, router, useForm } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useLocale } from '@/hooks/use-locale';
import { DeleteRecordAction } from '@/components/delete-record-action';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatCurrency, formatDateTime } from '@/lib/format';
import { index } from '@/routes/customers';
import { store as storeAddress, update as updateAddress, destroy as destroyAddress } from '@/routes/customers/address';
import { store as storePayment, destroy as destroyPayment } from '@/routes/customers/payments';
import { store as requestPaymentDeletion } from '@/routes/customers/payments/deletion-request';

type Address = {
    id: number;
    street: string | null;
    city: string | null;
    province: string | null;
    country: string;
};

const formatAddress = (address: Address): string =>
    [address.street, address.city, address.province].filter(Boolean).join(', ') || '—';

type CreditSale = {
    id: number;
    sold_at: string;
    total_amount: string;
    volume: string;
    pump: { name: string } | null;
    fuel_type: { name: string; unit: string } | null;
};

type Payment = {
    id: number;
    paid_at: string;
    amount: string;
    payment_method: string;
    notes: string | null;
    deletion_pending: boolean;
};

type PaginatedData<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Customer = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    notes: string | null;
    credit_limit: string;
    outstanding_balance: number;
    address: Address | null;
};

type CustomerShowProps = {
    customer: Customer;
    availableAddresses: Address[];
    creditSales: PaginatedData<CreditSale>;
    payments: PaginatedData<Payment>;
};

export default function CustomerShow({ customer, availableAddresses, creditSales, payments }: CustomerShowProps) {
    const { t } = useLocale();
    const [addressOpen, setAddressOpen] = useState(false);
    const [editingAddress, setEditingAddress] = useState<Address | null>(null);
    const [addressMode, setAddressMode] = useState<'create' | 'assign'>('create');
    const [paymentOpen, setPaymentOpen] = useState(false);

    const blankAddress = {
        address_id: '',
        street: '',
        city: '',
        province: '',
        country: 'KH',
    };

    const blankPayment = {
        amount: '',
        payment_method: 'cash',
        notes: '',
        paid_at: '',
    };

    const addressForm = useForm(blankAddress);

    const paymentForm = useForm(blankPayment);

    const openCreateAddress = () => {
        setEditingAddress(null);
        setAddressMode('create');
        addressForm.setData(blankAddress);
        addressForm.clearErrors();
        setAddressOpen(true);
    };

    const openCreatePayment = () => {
        paymentForm.setData(blankPayment);
        paymentForm.clearErrors();
        setPaymentOpen(true);
    };

    const openEditAddress = (address: Address) => {
        setEditingAddress(address);
        setAddressMode('create');
        addressForm.setData({
            address_id: '',
            street: address.street ?? '',
            city: address.city ?? '',
            province: address.province ?? '',
            country: address.country,
        });
        addressForm.clearErrors();
        setAddressOpen(true);
    };

    const submitAddress = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingAddress) {
            addressForm.put(
                updateAddress({ customer: customer.id, address: editingAddress.id }).url,
                { onSuccess: () => setAddressOpen(false) },
            );
        } else {
            addressForm.post(storeAddress({ customer: customer.id }).url, {
                onSuccess: () => setAddressOpen(false),
            });
        }
    };

    const removeAddress = () => {
        if (confirm(t('addresses.delete_confirm'))) {
            router.delete(destroyAddress({ customer: customer.id }).url, {
                preserveScroll: true,
            });
        }
    };

    const submitPayment = (e: React.FormEvent) => {
        e.preventDefault();
        paymentForm.post(storePayment({ customer: customer.id }).url, {
            onSuccess: () => setPaymentOpen(false),
        });
    };

    const removePayment = (payment: Payment) => {
        if (confirm(t('common.delete') + '?')) {
            router.delete(destroyPayment({ customer: customer.id, payment: payment.id }).url, {
                preserveScroll: true,
            });
        }
    };

    const paymentMethodLabels: Record<string, string> = {
        cash: t('sales.cash'),
        card: t('sales.card'),
        bank_transfer: t('credit.bank_transfer'),
    };

    return (
        <>
            <Head title={customer.name} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{customer.name}</h2>
                        {customer.phone && (
                            <p className="text-sm text-muted-foreground">{customer.phone}</p>
                        )}
                    </div>
                    {customer.outstanding_balance > 0 && (
                        <Badge variant="destructive" className="self-start text-sm">
                            {t('customers.outstanding_balance')}:{' '}
                            {formatCurrency(customer.outstanding_balance)}
                        </Badge>
                    )}
                </div>

                {/* Tabs */}
                <Tabs defaultValue="addresses">
                    <TabsList>
                        <TabsTrigger value="addresses">{t('addresses.title')}</TabsTrigger>
                        <TabsTrigger value="credit">{t('customers.credit_history')}</TabsTrigger>
                        <TabsTrigger value="payments">{t('customers.payment_history')}</TabsTrigger>
                    </TabsList>

                    {/* Address Tab */}
                    <TabsContent value="addresses" className="space-y-4 pt-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-medium">{t('addresses.title')}</h3>
                            {!customer.address && (
                                <Button size="sm" onClick={openCreateAddress}>
                                    <Plus className="h-4 w-4" />
                                    {t('addresses.new_address')}
                                </Button>
                            )}
                        </div>
                        {customer.address ? (
                            <div className="flex items-start justify-between gap-4 rounded-lg border p-4">
                                <div className="flex items-start gap-2">
                                    <MapPin className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                    <div className="space-y-0.5">
                                        <p className="font-medium">{customer.address.street ?? '—'}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {[customer.address.city, customer.address.province, customer.address.country]
                                                .filter(Boolean)
                                                .join(', ') || '—'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex shrink-0">
                                    <Button variant="ghost" size="icon" onClick={() => openEditAddress(customer.address!)}>
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={removeAddress}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                {t('addresses.no_address')}
                            </p>
                        )}
                    </TabsContent>

                    {/* Credit Sales Tab */}
                    <TabsContent value="credit" className="space-y-4 pt-4">
                        <h3 className="font-medium">{t('customers.credit_history')}</h3>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('common.when')}</TableHead>
                                    <TableHead>{t('common.pump')}</TableHead>
                                    <TableHead>{t('common.fuel')}</TableHead>
                                    <TableHead className="text-right">{t('common.volume')}</TableHead>
                                    <TableHead className="text-right">{t('common.total')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {creditSales.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                                            {t('credit.no_credit_sales')}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {creditSales.data.map((sale) => (
                                    <TableRow key={sale.id}>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDateTime(sale.sold_at)}
                                        </TableCell>
                                        <TableCell>{sale.pump?.name ?? '—'}</TableCell>
                                        <TableCell>{sale.fuel_type?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {sale.volume} {sale.fuel_type?.unit ?? ''}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums font-medium">
                                            {formatCurrency(sale.total_amount)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TabsContent>

                    {/* Payments Tab */}
                    <TabsContent value="payments" className="space-y-4 pt-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-medium">{t('customers.payment_history')}</h3>
                            <Button size="sm" onClick={openCreatePayment}>
                                <Plus className="h-4 w-4" />
                                {t('customers.record_payment')}
                            </Button>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('common.when')}</TableHead>
                                    <TableHead>{t('common.payment')}</TableHead>
                                    <TableHead>{t('common.description')}</TableHead>
                                    <TableHead className="text-right">{t('common.amount')}</TableHead>
                                    <TableHead className="text-right">{t('common.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                                            {t('cash_flow.no_entries')}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {payments.data.map((payment) => (
                                    <TableRow key={payment.id}>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDateTime(payment.paid_at)}
                                        </TableCell>
                                        <TableCell>
                                            {paymentMethodLabels[payment.payment_method] ?? payment.payment_method}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">{payment.notes ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums font-medium text-green-600">
                                            {formatCurrency(payment.amount)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <DeleteRecordAction
                                                compact
                                                pending={payment.deletion_pending}
                                                requestUrl={
                                                    requestPaymentDeletion({ customer: customer.id, payment: payment.id }).url
                                                }
                                                onDelete={() => removePayment(payment)}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Address Dialog */}
            <Dialog open={addressOpen} onOpenChange={setAddressOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingAddress ? t('addresses.edit_address') : t('addresses.new_address')}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitAddress} className="space-y-4">
                        {!editingAddress && availableAddresses.length > 0 && (
                            <div className="grid grid-cols-2 gap-2 rounded-md bg-muted p-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={addressMode === 'create' ? 'default' : 'ghost'}
                                    onClick={() => setAddressMode('create')}
                                >
                                    {t('addresses.create_new')}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={addressMode === 'assign' ? 'default' : 'ghost'}
                                    onClick={() => setAddressMode('assign')}
                                >
                                    {t('addresses.assign_existing')}
                                </Button>
                            </div>
                        )}

                        {!editingAddress && addressMode === 'assign' ? (
                            <div className="grid gap-2">
                                <Label>{t('addresses.title')}</Label>
                                <Select
                                    value={addressForm.data.address_id}
                                    onValueChange={(v) => addressForm.setData('address_id', v)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('addresses.select_address')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableAddresses.map((address) => (
                                            <SelectItem key={address.id} value={String(address.id)}>
                                                {formatAddress(address)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={addressForm.errors.address_id} />
                            </div>
                        ) : (
                            <>
                                {editingAddress && (
                                    <p className="text-xs text-muted-foreground">{t('addresses.shared_note')}</p>
                                )}
                                <div className="grid gap-2">
                                    <Label htmlFor="street">{t('addresses.street')}</Label>
                                    <Input
                                        id="street"
                                        value={addressForm.data.street}
                                        onChange={(e) => addressForm.setData('street', e.target.value)}
                                    />
                                    <InputError message={addressForm.errors.street} />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="city">{t('addresses.city')}</Label>
                                        <Input
                                            id="city"
                                            value={addressForm.data.city}
                                            onChange={(e) => addressForm.setData('city', e.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="province">{t('addresses.province')}</Label>
                                        <Input
                                            id="province"
                                            value={addressForm.data.province}
                                            onChange={(e) => addressForm.setData('province', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </>
                        )}
                        <DialogFooter>
                            <Button type="submit" disabled={addressForm.processing}>
                                {editingAddress ? t('common.save_changes') : t('common.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Payment Dialog */}
            <Dialog open={paymentOpen} onOpenChange={setPaymentOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('customers.record_payment')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitPayment} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="amount">{t('credit.amount')}</Label>
                            <Input
                                id="amount"
                                type="number"
                                inputMode="decimal"
                                step="0.01"
                                min="0.01"
                                value={paymentForm.data.amount}
                                onChange={(e) => paymentForm.setData('amount', e.target.value)}
                                placeholder="0.00"
                            />
                            <InputError message={paymentForm.errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label>{t('credit.payment_method')}</Label>
                            <Select
                                value={paymentForm.data.payment_method}
                                onValueChange={(v) => paymentForm.setData('payment_method', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cash">{t('sales.cash')}</SelectItem>
                                    <SelectItem value="card">{t('sales.card')}</SelectItem>
                                    <SelectItem value="bank_transfer">{t('credit.bank_transfer')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={paymentForm.errors.payment_method} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="notes">{t('customers.notes')}</Label>
                            <Input
                                id="notes"
                                value={paymentForm.data.notes}
                                onChange={(e) => paymentForm.setData('notes', e.target.value)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={paymentForm.processing}>
                                {t('credit.record_payment')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

CustomerShow.layout = {
    breadcrumbs: [
        { title: 'customers.title', href: index().url },
        { title: 'customers.detail' },
    ],
};
