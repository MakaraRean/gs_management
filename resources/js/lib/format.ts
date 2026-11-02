import type { Currency } from '@/types';

const moneyFormatters = new Map<number, Intl.NumberFormat>();

function moneyFormatter(digits: number): Intl.NumberFormat {
    let formatter = moneyFormatters.get(digits);

    if (!formatter) {
        formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
            signDisplay: 'negative',
        });
        moneyFormatters.set(digits, formatter);
    }

    return formatter;
}

const numberFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 2,
});

/**
 * An amount with the currency's own symbol, rounded to the digits that currency uses
 * (USD 2, KHR 0, ...). Without a currency it is a plain number.
 */
export function formatMoney(
    value: number | string | null | undefined,
    currency: Pick<Currency, 'symbol' | 'round_digits'> | null | undefined,
): string {
    const amount = Number(value ?? 0);

    if (!currency) {
        return numberFormatter.format(amount);
    }

    const formatted = moneyFormatter(currency.round_digits).format(amount);

    return formatted.startsWith('-')
        ? `-${currency.symbol}${formatted.slice(1)}`
        : `${currency.symbol}${formatted}`;
}

export function formatNumber(
    value: number | string | null | undefined,
): string {
    return numberFormatter.format(Number(value ?? 0));
}

export function formatVolume(
    value: number | string | null | undefined,
    unit = 'L',
): string {
    return `${numberFormatter.format(Number(value ?? 0))} ${unit}`;
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    // A date-only value ("2026-10-29") is a calendar day, which JavaScript parses as UTC
    // midnight; format it in UTC so it shows the same day in every time zone.
    const isDateOnly = /^\d{4}-\d{2}-\d{2}$/.test(value);

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: isDateOnly ? 'UTC' : undefined,
    });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
