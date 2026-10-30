<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * When the code that stores deliveries in UTC went live in production (UTC).
     * Deliveries created from then on are already stored in UTC.
     */
    private const STORED_IN_UTC_SINCE = '2026-10-30 07:46:33';

    /**
     * Deliveries were typed in Phnom Penh time (UTC+7) but stored as if it were UTC.
     * Store the UTC moment instead, like every other timestamp.
     */
    public function up(): void
    {
        $this->shiftDeliveredAt(-7);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->shiftDeliveredAt(7);
    }

    private function shiftDeliveredAt(int $hours): void
    {
        $shifted = DB::getDriverName() === 'sqlite'
            ? sprintf("DATETIME(delivered_at, '%+d hours')", $hours)
            : sprintf('DATE_ADD(delivered_at, INTERVAL %d HOUR)', $hours);

        DB::table('fuel_deliveries')
            ->where(fn ($query) => $query
                ->where('created_at', '<', self::STORED_IN_UTC_SINCE)
                ->orWhereNull('created_at'))
            ->update(['delivered_at' => DB::raw($shifted)]);
    }
};
