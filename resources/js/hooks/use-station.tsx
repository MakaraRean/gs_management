import { router, usePage } from '@inertiajs/react';
import type { Currency } from '@/types';

export function useStation() {
    const { current_station, stations } = usePage().props;

    const setStation = (id: number): void => {
        router.post('/station', { station_id: id }, { preserveState: false });
    };

    return {
        currentStation: current_station as { id: number; name: string; business_id: number; currency: Currency } | null,
        stations: stations as Array<{ id: number; name: string; business_id: number }>,
        setStation,
    };
}
