import type { HTMLAttributes } from 'react';
import type { SupportedLocale } from '@/hooks/use-locale';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';

export function LanguageSwitcher({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { locale, t, setLocale } = useLocale();

    const langs: SupportedLocale[] = ['km', 'en'];

    return (
        <div className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800', className)} {...props}>
            {langs.map((lang) => (
                <button
                    key={lang}
                    onClick={() => lang !== locale && setLocale(lang)}
                    className={cn(
                        'rounded-md px-3.5 py-1.5 text-sm transition-colors',
                        locale === lang
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    {t(`lang.${lang}`)}
                </button>
            ))}
        </div>
    );
}
