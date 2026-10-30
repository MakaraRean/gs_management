<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Dates are stored in UTC, but a day on any page or report is a calendar day
 * in the stations' local time zone (`app.local_timezone`).
 */
class LocalTime
{
    public static function timezone(): string
    {
        return config('app.local_timezone');
    }

    /**
     * Today's local date, at local midnight.
     */
    public static function today(): CarbonInterface
    {
        return Date::today(self::timezone());
    }

    /**
     * Read a date or date-time typed into a form as local time, in UTC for storage.
     */
    public static function toUtc(?string $value): ?CarbonInterface
    {
        return filled($value) ? Date::parse($value, self::timezone())->utc() : null;
    }

    /**
     * The local calendar date of a stored moment, as `Y-m-d`.
     */
    public static function dateOf(CarbonInterface $moment): string
    {
        return $moment->copy()->setTimezone(self::timezone())->toDateString();
    }

    /**
     * The UTC moment a local day starts.
     *
     * The day is a `Y-m-d` string, or the date of a Carbon in its own time zone,
     * such as a `from` filter or LocalTime::today().
     */
    public static function startOfDay(CarbonInterface|string $day): CarbonInterface
    {
        return self::localDay($day)->utc();
    }

    /**
     * The UTC moment a local day ends.
     */
    public static function endOfDay(CarbonInterface|string $day): CarbonInterface
    {
        return self::localDay($day)->endOfDay()->utc();
    }

    /**
     * UTC bounds covering the local days from `$from` through `$to`, for whereBetween().
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public static function dayRange(CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        return [self::startOfDay($from), self::endOfDay($to)];
    }

    /**
     * SQL for the local calendar date of a UTC datetime column, on MySQL/MariaDB or SQLite.
     *
     * Uses the zone's current UTC offset, which is exact for zones without
     * daylight saving time, such as Asia/Phnom_Penh.
     */
    public static function dateSql(string $column): string
    {
        $offset = self::today()->getOffset();

        return DB::connection()->getDriverName() === 'sqlite'
            ? sprintf("DATE(%s, '%+d seconds')", $column, $offset)
            : sprintf('DATE(DATE_ADD(%s, INTERVAL %d SECOND))', $column, $offset);
    }

    private static function localDay(CarbonInterface|string $day): CarbonInterface
    {
        $date = $day instanceof CarbonInterface ? $day->toDateString() : $day;

        return Date::parse($date, self::timezone())->startOfDay();
    }
}
