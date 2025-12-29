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
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->string('exercise_name');
            $table->integer('set_number');
            $table->integer('reps')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->string('unit')->default('kg');
            $table->text('notes')->nullable();
            
            // Indexes for performance
            $table->index('workout_id');
            $table->index('exercise_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
