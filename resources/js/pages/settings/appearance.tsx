import { Head } from '@inertiajs/react';
import { useLocale } from '@/hooks/use-locale';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useLocale();

    return (
        <>
            <Head title={t('settings.appearance_title')} />

            <h1 className="sr-only">{t('settings.appearance_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('settings.appearance_title')}
                    description={t('settings.appearance_description')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'settings.appearance_title',
            href: editAppearance(),
        },
    ],
};
