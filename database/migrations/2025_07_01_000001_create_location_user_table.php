<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only create if locations and users tables exist
        if (Schema::hasTable('locations') && Schema::hasTable('users')) {
            Schema::create('location_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('location_id')->constrained()->onDelete('cascade');
                $table->string('role'); // manager or employee
                $table->string('status')->default('active'); // active, pending, etc
                $table->timestamps();
                $table->unique(['user_id', 'location_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};
