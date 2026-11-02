import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import { useLocale } from '@/hooks/use-locale';
import type { Currency } from '@/types';

// Radix does not allow an empty item value, so "inherit" travels under this value.
const INHERIT = 'inherit';

export function currencyLabel(currency: Currency, locale: string): string {
    const name = locale === 'km' ? currency.name_km : currency.name;

    return `${currency.code} – ${name} (${currency.symbol})`;
}

type Props = {
    id?: string;
    /** The selected currency id as a string; empty means none (or inherit, with `inheritLabel`). */
    value: string;
    onValueChange: (value: string) => void;
    /** Adds a first option that clears the value, e.g. "Same as business (USD)". */
    inheritLabel?: string;
    /** Limits the choices to these currency ids. */
    onlyIds?: number[];
};

export function CurrencySelect({
    id,
    value,
    onValueChange,
    inheritLabel,
    onlyIds,
}: Props) {
    const { t, locale } = useLocale();
    const { currencies } = useCurrency();

    const options = currencies.filter(
        (currency) =>
            (currency.is_active || String(currency.id) === value) &&
            (!onlyIds || onlyIds.includes(currency.id)),
    );

    return (
        <Select
            value={value === '' && inheritLabel ? INHERIT : value}
            onValueChange={(next) =>
                onValueChange(next === INHERIT ? '' : next)
            }
        >
            <SelectTrigger id={id} className="w-full">
                <SelectValue placeholder={t('currencies.select_currency')} />
            </SelectTrigger>
            <SelectContent>
                {inheritLabel && (
                    <SelectItem value={INHERIT}>{inheritLabel}</SelectItem>
                )}
                {options.map((currency) => (
                    <SelectItem key={currency.id} value={String(currency.id)}>
                        {currencyLabel(currency, locale)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
