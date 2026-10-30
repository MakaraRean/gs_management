<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        DB::table('fuel_deliveries')->update(['delivered_at' => DB::raw($shifted)]);
    }
};
