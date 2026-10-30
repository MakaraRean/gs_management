const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 2,
});

export function formatCurrency(
    value: number | string | null | undefined,
): string {
    return currencyFormatter.format(Number(value ?? 0));
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
