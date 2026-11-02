import { usePage } from '@inertiajs/react';
import { formatMoney, formatMoneyCompact } from '@/lib/format';
import type { Currency, CurrentStation } from '@/types';

export function useCurrency() {
    const { current_station, currencies } = usePage().props as unknown as {
        current_station: CurrentStation | null;
        currencies: Currency[];
    };
    const currency = current_station?.currency ?? null;

    /**
     * Format an amount in the active station's currency, or in the currency a record was
     * stored with when its `currency_id` is given.
     */
    const formatCurrency = (
        value: number | string | null | undefined,
        currencyId?: number | null,
    ): string => {
        const recordCurrency =
            currencyId == null
                ? null
                : currencies.find((item) => item.id === currencyId);

        return formatMoney(value, recordCurrency ?? currency);
    };

    const formatCompactCurrency = (value: number | string | null | undefined): string =>
        formatMoneyCompact(value, currency);

    return { currency, currencies, formatCurrency, formatCompactCurrency };
}
