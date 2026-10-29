<?php

use App\Models\Address;
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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->after('station_id')->constrained()->nullOnDelete();
        });

        // Backfill each customer's single address from the old pivot,
        // preferring the default (then the first active) linked address.
        $seen = [];

        DB::table('customer_address')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get(['customer_id', 'address_id'])
            ->each(function ($row) use (&$seen) {
                if (isset($seen[$row->customer_id])) {
                    return;
                }

                $seen[$row->customer_id] = true;

                DB::table('customers')
                    ->where('id', $row->customer_id)
                    ->update(['address_id' => $row->address_id]);
            });

        Schema::dropIfExists('customer_address');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('customer_address', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('home');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('customers')
            ->whereNotNull('address_id')
            ->get(['id', 'address_id'])
            ->each(function ($row) {
                DB::table('customer_address')->insert([
                    'customer_id' => $row->id,
                    'address_id' => $row->address_id,
                    'label' => 'home',
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeignIdFor(Address::class);
            $table->dropColumn('address_id');
        });
    }
};
