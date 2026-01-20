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
        Schema::table('workouts', function (Blueprint $table) {
            $table->string('type')->default('strength'); // strength, crossfit, cardio, etc.
            $table->json('custom_content')->nullable(); // Flexible JSON structure
            $table->text('raw_text')->nullable(); // Original text (OCR or manual)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn(['type', 'custom_content', 'raw_text']);
        });
    }
};
