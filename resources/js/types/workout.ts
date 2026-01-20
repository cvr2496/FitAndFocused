/**
 * Workout Photo Logger - Type Definitions
 * Mobile-first workout tracking via photo upload
 */

/**
 * Confidence level for AI extraction
 */
export type ConfidenceLevel = 'high' | 'low';

/**
 * Weight unit
 */
export type WeightUnit = 'kg' | 'lbs';

/**
 * Individual set within an exercise
 */
export interface Set {
    reps: number | null;
    weight: number | null;
    unit: WeightUnit;
    notes: string | null;
    confidence: ConfidenceLevel;
    time_seconds?: number | null;
    distance_meters?: number | null;
}

/**
 * Exercise with multiple sets
 */
export interface Exercise {
    name: string;
    sets: Set[];
}

/**
 * Valid JSON-serializable content for custom workout data
 * Mirrors Inertia's FormDataConvertible requirements
 */
export type WorkoutCustomContent = Record<string, string | number | boolean | null | undefined | Array<string | number | boolean | null | undefined> | Record<string, string | number | boolean | null | undefined>>;

/**
 * Complete workout data
 */
export interface Workout {
    id?: number;
    date: string; // YYYY-MM-DD format
    title: string | null;
    type?: 'strength' | 'crossfit' | 'cardio' | 'other';
    photo_path: string | null;
    raw_text?: string | null;
    notes: string | null;
    exercises: Exercise[];
    metrics?: {
        total_time_seconds?: number | null;
        total_rounds?: number | null;
        score?: string | null;
    } | null;
    custom_content?: WorkoutCustomContent;
    created_at?: string;
    updated_at?: string;
}


/**
 * Response from photo upload/extraction endpoint
 */
export interface UploadResponse {
    success: boolean;
    data?: Workout;
    error?: string;
    message?: string;
}

/**
 * Set data for database storage (flattened structure)
 */
export interface SetRecord {
    id?: number;
    workout_id: number;
    exercise_name: string;
    set_number: number;
    reps: number | null;
    weight: number | null;
    unit: WeightUnit;
    notes: string | null;
}

/**
 * Workout with flattened sets for API responses
 */
export interface WorkoutWithSets extends Omit<Workout, 'exercises'> {
    sets: SetRecord[];
}

/**
 * Stats summary
 */
export interface WorkoutStats {
    total_workouts: number;
    this_week: number;
    total_sets: number;
    total_volume: number; // sum of (weight × reps)
}

