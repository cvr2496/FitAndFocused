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
        // Update existing workouts to assign them to the first user before adding constraint
        $firstUserId = DB::table('users')->value('id');
        
        Schema::table('workouts', function (Blueprint $table) use ($firstUserId) {
            $table->foreignId('user_id')
                ->after('id')
                ->default($firstUserId ?? 1)
                ->constrained()
                ->onDelete('cascade');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
