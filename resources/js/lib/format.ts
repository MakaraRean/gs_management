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

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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
