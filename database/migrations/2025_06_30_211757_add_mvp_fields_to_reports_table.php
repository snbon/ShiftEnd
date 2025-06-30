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
            // Add shift time fields
            $table->time('shift_start_time')->nullable()->after('date');
            $table->time('shift_end_time')->nullable()->after('shift_start_time');

            // Add total sales field
            $table->decimal('total_sales', 10, 2)->default(0)->after('total_bank');

            // Add cash drawer fields
            $table->decimal('opening_cash', 10, 2)->default(0)->after('total_sales');
            $table->decimal('closing_cash', 10, 2)->default(0)->after('opening_cash');
            $table->decimal('cash_difference', 10, 2)->default(0)->after('closing_cash');

            // Add detailed tips fields
            $table->decimal('tips_cash', 10, 2)->default(0)->after('total_tips');
            $table->decimal('tips_card', 10, 2)->default(0)->after('tips_cash');

            // Add status and approval fields
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->after('comments');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'shift_start_time', 'shift_end_time', 'total_sales',
                'opening_cash', 'closing_cash', 'cash_difference',
                'tips_cash', 'tips_card', 'status', 'approved_by', 'approved_at'
            ]);
        });
    }
};
