import { Head, Link, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useLocale } from '@/hooks/use-locale';
import InputError from '@/components/input-error';
import { LanguageSwitcher } from '@/components/language-switcher';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import businessStore from '@/routes/onboarding/business';

export default function OnboardingIndex() {
    const { t } = useLocale();

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone: '',
        email: '',
        address: '',
        customers_per_station: false,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(businessStore.store().url);
    };

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <Head title={t('onboarding.title')} />

            <div className="absolute top-4 right-4">
                <LanguageSwitcher />
            </div>

            <div className="w-full max-w-md">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <div className="flex h-16 w-16 items-center justify-center rounded-md">
                            <img
                                src="/images/logo.jpg"
                                alt="Gas Station Logo"
                                className="size-16 object-contain"
                            />
                        </div>

                        <div className="space-y-2 text-center">
                            <h1 className="flex items-center justify-center gap-2 text-xl font-medium">
                                <Building2 className="h-5 w-5 text-muted-foreground" />
                                {t('onboarding.business_step_title')}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {t('onboarding.business_step_description')}
                            </p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('common.name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                autoFocus
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
                            <Label htmlFor="email">{t('common.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} />
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
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="customers_per_station"
                                checked={data.customers_per_station}
                                onCheckedChange={(checked) =>
                                    setData('customers_per_station', !!checked)
                                }
                            />
                            <Label htmlFor="customers_per_station">
                                {t('businesses.customers_per_station')}
                            </Label>
                            <InputError message={errors.customers_per_station} />
                        </div>
                        <Button type="submit" className="w-full" disabled={processing}>
                            {t('onboarding.continue')}
                        </Button>
                    </form>

                    <div className="text-center">
                        <Link
                            href={dashboard()}
                            className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                        >
                            {t('onboarding.skip_for_now')}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
