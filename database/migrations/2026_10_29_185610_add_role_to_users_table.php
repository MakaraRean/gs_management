<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('business_id');
        });

        // Backfill: business owners become admins; existing staff start as cashiers.
        DB::table('users')
            ->whereIn('id', DB::table('businesses')->whereNotNull('user_id')->select('user_id'))
            ->update(['role' => 'admin']);

        DB::table('users')
            ->whereNotNull('business_id')
            ->whereNull('role')
            ->update(['role' => 'cashier']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
