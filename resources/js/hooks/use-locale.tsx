import { router, usePage } from '@inertiajs/react';

export type SupportedLocale = 'km' | 'en';

export function useLocale() {
    const { locale, translations } = usePage().props;

    const t = (key: string, replacements?: Record<string, string>): string => {
        let value = (translations as Record<string, string>)[key] ?? key;

        if (replacements) {
            for (const [placeholder, replacement] of Object.entries(replacements)) {
                value = value.replace(`:${placeholder}`, replacement);
            }
        }

        return value;
    };

    const setLocale = (newLocale: SupportedLocale): void => {
        const maxAge = 365 * 24 * 60 * 60;
        document.cookie = `locale=${newLocale};path=/;max-age=${maxAge};SameSite=Lax`;

        router.post('/locale', { locale: newLocale }, { preserveScroll: true, preserveState: false });
    };

    return { locale: locale as SupportedLocale, t, setLocale };
}
