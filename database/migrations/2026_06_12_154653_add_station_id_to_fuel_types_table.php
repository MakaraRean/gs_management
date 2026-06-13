<?php

use App\Models\Station;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fuel_types', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('id')->constrained()->nullOnDelete()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_types', function (Blueprint $table) {
            $table->dropForeignIdFor(Station::class);
            $table->dropColumn('station_id');
        });
    }
};
