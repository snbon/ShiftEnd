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
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'report_date')) {
                $table->date('report_date')->nullable()->after('location_id');
            }
            if (!Schema::hasColumn('reports', 'shift_start_time')) {
                $table->time('shift_start_time')->nullable()->after('report_date');
            }
            if (!Schema::hasColumn('reports', 'shift_end_time')) {
                $table->time('shift_end_time')->nullable()->after('shift_start_time');
            }
            if (!Schema::hasColumn('reports', 'cash_sales')) {
                $table->decimal('cash_sales', 10, 2)->default(0)->after('shift_end_time');
            }
            if (!Schema::hasColumn('reports', 'card_sales')) {
                $table->decimal('card_sales', 10, 2)->default(0)->after('cash_sales');
            }
            if (!Schema::hasColumn('reports', 'total_sales')) {
                $table->decimal('total_sales', 10, 2)->default(0)->after('card_sales');
            }
            if (!Schema::hasColumn('reports', 'opening_cash')) {
                $table->decimal('opening_cash', 10, 2)->default(0)->after('total_sales');
            }
            if (!Schema::hasColumn('reports', 'closing_cash')) {
                $table->decimal('closing_cash', 10, 2)->default(0)->after('opening_cash');
            }
            if (!Schema::hasColumn('reports', 'cash_difference')) {
                $table->decimal('cash_difference', 10, 2)->default(0)->after('closing_cash');
            }
            if (!Schema::hasColumn('reports', 'tips_cash')) {
                $table->decimal('tips_cash', 10, 2)->default(0)->after('cash_difference');
            }
            if (!Schema::hasColumn('reports', 'tips_card')) {
                $table->decimal('tips_card', 10, 2)->default(0)->after('tips_cash');
            }
            if (!Schema::hasColumn('reports', 'total_tips')) {
                $table->decimal('total_tips', 10, 2)->default(0)->after('tips_card');
            }
            if (!Schema::hasColumn('reports', 'inventory_notes')) {
                $table->text('inventory_notes')->nullable()->after('total_tips');
            }
            if (!Schema::hasColumn('reports', 'shift_notes')) {
                $table->text('shift_notes')->nullable()->after('inventory_notes');
            }
            if (!Schema::hasColumn('reports', 'status')) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->after('shift_notes');
            }
            if (!Schema::hasColumn('reports', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->after('status');
            }
            if (!Schema::hasColumn('reports', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            foreach ([
                'report_date', 'shift_start_time', 'shift_end_time', 'cash_sales', 'card_sales', 'total_sales',
                'opening_cash', 'closing_cash', 'cash_difference', 'tips_cash', 'tips_card', 'total_tips',
                'inventory_notes', 'shift_notes', 'status', 'approved_by', 'approved_at'
            ] as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    if ($column === 'approved_by') {
                        $table->dropForeign(['approved_by']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
