import { useLocale } from '@/hooks/use-locale';
import { useStation } from '@/hooks/use-station';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export function StationSwitcher() {
    const { currentStation, stations, setStation } = useStation();
    const { t } = useLocale();

    if (stations.length <= 1) {
        return null;
    }

    return (
        <div className="px-2 py-1">
            <Select
                value={currentStation?.id?.toString() ?? ''}
                onValueChange={(value) => setStation(Number(value))}
            >
                <SelectTrigger className="w-full">
                    <SelectValue placeholder={t('stations.select_station')} />
                </SelectTrigger>
                <SelectContent>
                    {stations.map((station) => (
                        <SelectItem key={station.id} value={station.id.toString()}>
                            {station.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
