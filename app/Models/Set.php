<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workout_id
 * @property string $exercise_name
 * @property int $set_number
 * @property int|null $reps
 * @property float|null $weight
 * @property string $unit
 * @property string|null $notes
 */
class Set extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workout_id',
        'exercise_name',
        'set_number',
        'reps',
        'weight',
        'unit',
        'notes',
    ];

    protected $casts = [
        'reps' => 'integer',
        'set_number' => 'integer',
        'weight' => 'float',
    ];

    /**
     * A set belongs to a workout
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
