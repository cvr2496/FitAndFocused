<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'title',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * A workout belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A workout has many sets
     */
    public function sets(): HasMany
    {
        return $this->hasMany(Set::class);
    }

    /**
     * Get the total number of sets in this workout
     */
    public function getTotalSets(): int
    {
        return $this->sets()->count();
    }

    /**
     * Get exercises grouped by name with their sets
     */
    public function getExercises(): array
    {
        return $this->sets()
            ->orderBy('set_number')
            ->get()
            ->groupBy('exercise_name')
            ->map(function ($sets) {
                /** @var Set $firstSet */
                $firstSet = $sets->first();
                return [
                    'name' => $firstSet->exercise_name,
                    'sets' => $sets->values(),
                ];
            })
            ->values()
            ->toArray();
    }
}
