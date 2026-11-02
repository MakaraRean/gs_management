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
     * Existing businesses start in USD, the currency amounts have always been shown in. A station's own
     * currency is optional: NULL means it uses its business's currency.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('address')->constrained()->restrictOnDelete();
        });

        DB::table('businesses')->update([
            'currency_id' => DB::table('currencies')->where('code', 'USD')->value('id'),
        ]);

        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable(false)->change();
        });

        Schema::table('stations', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('address')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });
    }
};
