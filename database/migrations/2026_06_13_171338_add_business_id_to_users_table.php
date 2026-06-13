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
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Backfill: each business owner becomes a member of the business they created.
        DB::table('businesses')->orderBy('id')->each(function (object $business): void {
            DB::table('users')
                ->where('id', $business->user_id)
                ->whereNull('business_id')
                ->update(['business_id' => $business->id]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
