import { Head, Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useLocale } from '@/hooks/use-locale';
import { Button } from '@/components/ui/button';
import { show as onboardingShow } from '@/routes/onboarding';

export default function DashboardLocked() {
    const { t } = useLocale();

    return (
        <>
            <Head title={t('dashboard.title')} />

            <div className="flex min-h-[60vh] flex-col items-center justify-center gap-6 text-center">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                    <Lock className="h-8 w-8 text-muted-foreground" />
                </div>
                <div className="space-y-2">
                    <h1 className="text-xl font-medium">
                        {t('dashboard.locked_title')}
                    </h1>
                    <p className="max-w-md text-sm text-muted-foreground">
                        {t('dashboard.locked_description')}
                    </p>
                </div>
                <div className="flex flex-col items-center gap-3">
                    <Button asChild>
                        <Link href={onboardingShow()}>
                            {t('dashboard.locked_cta')}
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
