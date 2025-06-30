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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('total_customers')->default(0);
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_bank', 10, 2)->default(0);
            $table->decimal('total_vouchers', 10, 2)->default(0);
            $table->decimal('total_tips', 10, 2)->default(0);
            $table->decimal('total_refunds', 10, 2)->default(0);
            $table->text('waste_notes')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
