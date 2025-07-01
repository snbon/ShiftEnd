<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Migrate existing users to location_user
        $users = DB::table('users')->whereNotNull('location_id')->get();
        foreach ($users as $user) {
            // Only migrate if not owner of the location
            $location = DB::table('locations')->where('id', $user->location_id)->first();
            if ($location && $location->owner_id != $user->id) {
                DB::table('location_user')->insert([
                    'user_id' => $user->id,
                    'location_id' => $user->location_id,
                    'role' => $user->role ?? 'employee',
                    'status' => $user->status ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        // Optionally, drop location_id and role columns from users table
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn(['location_id', 'role']);
        // });
    }

    public function down(): void
    {
        // Optionally, add columns back (not recommended for production)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('role')->nullable();
        });
        // No need to move data back in down()
    }
};
