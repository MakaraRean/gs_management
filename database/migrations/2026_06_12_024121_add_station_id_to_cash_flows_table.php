<?php

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
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });
    }
};
