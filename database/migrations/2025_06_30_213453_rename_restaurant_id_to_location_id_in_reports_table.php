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
            // Drop the unique constraint first
            $table->dropUnique(['restaurant_id', 'date']);

            // Drop the foreign key constraint
            $table->dropForeign(['restaurant_id']);

            // Rename the column
            $table->renameColumn('restaurant_id', 'location_id');

            // Add the new foreign key constraint
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['location_id']);

            // Rename the column back
            $table->renameColumn('location_id', 'restaurant_id');

            // Add the old foreign key constraint
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');

            // Re-add the unique constraint
            $table->unique(['restaurant_id', 'date']);
        });
    }
};
