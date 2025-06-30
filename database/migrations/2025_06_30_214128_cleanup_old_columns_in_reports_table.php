<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // First, migrate data from old columns to new ones if they exist
            if (Schema::hasColumn('reports', 'date') && Schema::hasColumn('reports', 'report_date')) {
                // Copy data from 'date' to 'report_date' where report_date is null
                DB::statement('UPDATE reports SET report_date = date WHERE report_date IS NULL');
            }

            if (Schema::hasColumn('reports', 'total_cash') && Schema::hasColumn('reports', 'cash_sales')) {
                // Copy data from 'total_cash' to 'cash_sales' where cash_sales is 0
                DB::statement('UPDATE reports SET cash_sales = total_cash WHERE cash_sales = 0');
            }

            if (Schema::hasColumn('reports', 'total_bank') && Schema::hasColumn('reports', 'card_sales')) {
                // Copy data from 'total_bank' to 'card_sales' where card_sales is 0
                DB::statement('UPDATE reports SET card_sales = total_bank WHERE card_sales = 0');
            }

            if (Schema::hasColumn('reports', 'waste_notes') && Schema::hasColumn('reports', 'inventory_notes')) {
                // Copy data from 'waste_notes' to 'inventory_notes' where inventory_notes is null
                DB::statement('UPDATE reports SET inventory_notes = waste_notes WHERE inventory_notes IS NULL');
            }

            if (Schema::hasColumn('reports', 'comments') && Schema::hasColumn('reports', 'shift_notes')) {
                // Copy data from 'comments' to 'shift_notes' where shift_notes is null
                DB::statement('UPDATE reports SET shift_notes = comments WHERE shift_notes IS NULL');
            }
        });

        // Now drop the old columns
        Schema::table('reports', function (Blueprint $table) {
            $columnsToDrop = [
                'date', 'total_customers', 'total_cash', 'total_bank',
                'total_vouchers', 'total_refunds', 'waste_notes', 'comments'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Re-add the old columns
            $table->date('date')->nullable();
            $table->integer('total_customers')->default(0);
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_bank', 10, 2)->default(0);
            $table->decimal('total_vouchers', 10, 2)->default(0);
            $table->decimal('total_refunds', 10, 2)->default(0);
            $table->text('waste_notes')->nullable();
            $table->text('comments')->nullable();
        });

        // Migrate data back if needed
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'report_date') && Schema::hasColumn('reports', 'date')) {
                DB::statement('UPDATE reports SET date = report_date WHERE date IS NULL');
            }

            if (Schema::hasColumn('reports', 'cash_sales') && Schema::hasColumn('reports', 'total_cash')) {
                DB::statement('UPDATE reports SET total_cash = cash_sales WHERE total_cash = 0');
            }

            if (Schema::hasColumn('reports', 'card_sales') && Schema::hasColumn('reports', 'total_bank')) {
                DB::statement('UPDATE reports SET total_bank = card_sales WHERE total_bank = 0');
            }

            if (Schema::hasColumn('reports', 'inventory_notes') && Schema::hasColumn('reports', 'waste_notes')) {
                DB::statement('UPDATE reports SET waste_notes = inventory_notes WHERE waste_notes IS NULL');
            }

            if (Schema::hasColumn('reports', 'shift_notes') && Schema::hasColumn('reports', 'comments')) {
                DB::statement('UPDATE reports SET comments = shift_notes WHERE comments IS NULL');
            }
        });
    }
};
