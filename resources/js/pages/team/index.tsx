import { Head, router, useForm } from '@inertiajs/react';
import { Crown, Pencil, Plus, Trash2, Users } from 'lucide-react';
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
import { update as businessUpdate } from '@/routes/business';
import { index } from '@/routes/team';
import { destroy as memberDestroy, store as memberStore } from '@/routes/team/members';

type Business = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    customers_per_station: boolean;
};

type Member = {
    id: number;
    name: string;
    email: string;
    is_owner: boolean;
};

type TeamProps = {
    business: Business;
    members: Member[];
    isOwner: boolean;
};

export default function TeamIndex({ business, members, isOwner }: TeamProps) {
    const { t } = useLocale();
    const [memberOpen, setMemberOpen] = useState(false);
    const [businessOpen, setBusinessOpen] = useState(false);

    const memberForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const businessForm = useForm({
        name: business.name,
        phone: business.phone ?? '',
        email: business.email ?? '',
        address: business.address ?? '',
        customers_per_station: business.customers_per_station,
    });

    const openAddMember = () => {
        memberForm.reset();
        memberForm.clearErrors();
        setMemberOpen(true);
    };

    const submitMember = (event: React.FormEvent) => {
        event.preventDefault();
        memberForm.post(memberStore().url, { onSuccess: () => setMemberOpen(false) });
    };

    const submitBusiness = (event: React.FormEvent) => {
        event.preventDefault();
        businessForm.put(businessUpdate().url, { onSuccess: () => setBusinessOpen(false) });
    };

    const removeMember = (member: Member) => {
        if (confirm(t('team.remove_confirm'))) {
            router.delete(memberDestroy({ member: member.id }).url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={t('team.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <div>
                        <h2 className="text-lg font-medium">{business.name}</h2>
                        <p className="text-sm text-muted-foreground">{t('team.members')}</p>
                    </div>
                    {isOwner && (
                        <Button variant="outline" size="sm" onClick={() => setBusinessOpen(true)}>
                            <Pencil className="h-4 w-4" />
                            {t('team.edit_business')}
                        </Button>
                    )}
                </div>

                <div className="space-y-4">
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="flex items-center gap-2 text-sm font-medium">
                            <Users className="h-4 w-4 text-muted-foreground" />
                            {t('team.members')}
                        </h3>
                        {isOwner && (
                            <Button size="sm" onClick={openAddMember}>
                                <Plus className="h-4 w-4" />
                                {t('team.add_user')}
                            </Button>
                        )}
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('team.name')}</TableHead>
                                <TableHead>{t('team.email')}</TableHead>
                                <TableHead className="text-right">{t('common.actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={3} className="text-center text-muted-foreground">
                                        {t('team.no_members')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {members.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell>
                                        <span className="flex items-center gap-2 font-medium">
                                            {member.name}
                                            {member.is_owner && (
                                                <Badge variant="secondary" className="gap-1">
                                                    <Crown className="h-3 w-3" />
                                                    {t('team.owner')}
                                                </Badge>
                                            )}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">{member.email}</TableCell>
                                    <TableCell className="text-right">
                                        {isOwner && !member.is_owner && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive"
                                                onClick={() => removeMember(member)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={memberOpen} onOpenChange={setMemberOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('team.add_user')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitMember} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="member_name">{t('team.name')}</Label>
                            <Input
                                id="member_name"
                                value={memberForm.data.name}
                                onChange={(e) => memberForm.setData('name', e.target.value)}
                            />
                            <InputError message={memberForm.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member_email">{t('team.email')}</Label>
                            <Input
                                id="member_email"
                                type="email"
                                value={memberForm.data.email}
                                onChange={(e) => memberForm.setData('email', e.target.value)}
                            />
                            <InputError message={memberForm.errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member_password">{t('team.password')}</Label>
                            <Input
                                id="member_password"
                                type="password"
                                value={memberForm.data.password}
                                onChange={(e) => memberForm.setData('password', e.target.value)}
                            />
                            <InputError message={memberForm.errors.password} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member_password_confirmation">{t('team.confirm_password')}</Label>
                            <Input
                                id="member_password_confirmation"
                                type="password"
                                value={memberForm.data.password_confirmation}
                                onChange={(e) => memberForm.setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={memberForm.processing}>
                                {t('team.add_user')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={businessOpen} onOpenChange={setBusinessOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('team.edit_business')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitBusiness} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="business_name">{t('common.name')}</Label>
                            <Input
                                id="business_name"
                                value={businessForm.data.name}
                                onChange={(e) => businessForm.setData('name', e.target.value)}
                            />
                            <InputError message={businessForm.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="business_phone">{t('stations.phone')}</Label>
                            <Input
                                id="business_phone"
                                value={businessForm.data.phone}
                                onChange={(e) => businessForm.setData('phone', e.target.value)}
                            />
                            <InputError message={businessForm.errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="business_address">{t('stations.address')}</Label>
                            <Input
                                id="business_address"
                                value={businessForm.data.address}
                                onChange={(e) => businessForm.setData('address', e.target.value)}
                            />
                            <InputError message={businessForm.errors.address} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="business_customers_per_station"
                                checked={businessForm.data.customers_per_station}
                                onCheckedChange={(checked) => businessForm.setData('customers_per_station', !!checked)}
                            />
                            <Label htmlFor="business_customers_per_station">
                                {t('businesses.customers_per_station')}
                            </Label>
                            <InputError message={businessForm.errors.customers_per_station} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={businessForm.processing}>
                                {t('common.save_changes')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

TeamIndex.layout = {
    breadcrumbs: [{ title: 'team.title', href: index().url }],
};
