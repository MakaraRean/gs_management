<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The currency list is reference data and seeders are not run in production, so it ships with the migration.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();
            $table->string('name');
            $table->string('name_km');
            $table->string('symbol', 8);
            $table->unsignedTinyInteger('round_digits')->default(2)->comment('Decimal digits amounts are rounded and shown to; at most 2, the amount columns hold 2.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('currencies')->insert(array_map(
            fn (array $currency): array => $currency + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                ['code' => 'USD', 'name' => 'US Dollar', 'name_km' => 'ដុល្លារអាមេរិក', 'symbol' => '$', 'round_digits' => 2],
                ['code' => 'KHR', 'name' => 'Cambodian Riel', 'name_km' => 'រៀលកម្ពុជា', 'symbol' => '៛', 'round_digits' => 0],
                ['code' => 'THB', 'name' => 'Thai Baht', 'name_km' => 'បាតថៃ', 'symbol' => '฿', 'round_digits' => 2],
                ['code' => 'VND', 'name' => 'Vietnamese Dong', 'name_km' => 'ដុងវៀតណាម', 'symbol' => '₫', 'round_digits' => 0],
            ],
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
