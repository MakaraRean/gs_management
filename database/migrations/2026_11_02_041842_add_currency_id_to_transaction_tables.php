<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every table that holds an amount stores the currency it was recorded in.
     */
    private const TABLES = ['sales', 'cash_flows', 'customer_payments', 'fuel_deliveries'];

    /**
     * Run the migrations.
     *
     * Each record is backfilled with the effective currency of its station (records without one were
     * always shown in USD), and the report indexes let reports filter on station, currency and date without joins.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('currency_id')->nullable()->constrained()->restrictOnDelete();
            });
        }

        $effectiveCurrency = 'select coalesce(s.currency_id, b.currency_id) from stations s join businesses b on b.id = s.business_id';

        foreach (['sales', 'cash_flows', 'customer_payments'] as $tableName) {
            DB::table($tableName)->update([
                'currency_id' => DB::raw("({$effectiveCurrency} where s.id = {$tableName}.station_id)"),
            ]);
        }

        DB::table('fuel_deliveries')->update([
            'currency_id' => DB::raw('(select coalesce(s.currency_id, b.currency_id) from tanks t join stations s on s.id = t.station_id join businesses b on b.id = s.business_id where t.id = fuel_deliveries.tank_id)'),
        ]);

        $usd = DB::table('currencies')->where('code', 'USD')->value('id');

        foreach (self::TABLES as $tableName) {
            DB::table($tableName)->whereNull('currency_id')->update(['currency_id' => $usd]);

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('currency_id')->nullable(false)->change();
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['station_id', 'currency_id', 'sold_at'], 'sales_station_currency_sold_at_index');
        });

        Schema::table('cash_flows', function (Blueprint $table) {
            $table->index(['station_id', 'currency_id', 'occurred_at'], 'cash_flows_station_currency_occurred_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_station_currency_sold_at_index');
        });

        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropIndex('cash_flows_station_currency_occurred_at_index');
        });

        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('currency_id');
            });
        }
    }
};
