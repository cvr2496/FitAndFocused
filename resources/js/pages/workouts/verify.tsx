import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Workout, type Set, type Exercise } from '@/types/workout';
import { Head, useForm } from '@inertiajs/react';
import { AlertCircle, Check, Image as ImageIcon, Pencil, Plus, Trash, X } from 'lucide-react';
import { useState } from 'react';

interface VerifyWorkoutProps {
    workout: Workout;
    photoUrl: string | null;
}

export default function VerifyWorkout({ workout, photoUrl }: VerifyWorkoutProps) {
    const { data, setData, post, processing } = useForm<Workout>(workout);
    const [showPhoto, setShowPhoto] = useState(false);

    const handleSave = () => {
        post('/api/workouts/save', {
            onError: (errors) => {
                console.error('Failed to save workout:', errors);
                alert('Failed to save workout. Please check the console for details.');
            }
        });
    };

    const updateExerciseName = (exerciseIndex: number, newName: string) => {
        setData({
            ...data,
            exercises: data.exercises.map((exercise, idx) =>
                idx === exerciseIndex
                    ? { ...exercise, name: newName }
                    : exercise
            ),
        });
    };

    const addExercise = () => {
        const newExercise: Exercise = {
            name: 'New Exercise',
            sets: [
                {
                    reps: null,
                    weight: null,
                    unit: 'kg',
                    notes: null,
                    confidence: 'high',
                },
            ],
        };
        setData({
            ...data,
            exercises: [...data.exercises, newExercise],
        });
    };

    const removeExercise = (index: number) => {
        setData({
            ...data,
            exercises: data.exercises.filter((_, i) => i !== index),
        });
    };

    const addSet = (exerciseIndex: number) => {
        const newSet: Set = {
            reps: null,
            weight: null,
            unit: 'kg',
            notes: null,
            confidence: 'high',
        };
        setData({
            ...data,
            exercises: data.exercises.map((ex, i) =>
                i === exerciseIndex
                    ? { ...ex, sets: [...ex.sets, newSet] }
                    : ex
            ),
        });
    };

    const removeSet = (exerciseIndex: number, setIndex: number) => {
        setData({
            ...data,
            exercises: data.exercises.map((ex, i) =>
                i === exerciseIndex
                    ? { ...ex, sets: ex.sets.filter((_, j) => j !== setIndex) }
                    : ex
            ),
        });
    };

    const updateSet = (
        exerciseIndex: number,
        setIndex: number,
        field: keyof Set,
        value: string
    ) => {
        let parsedValue: string | number | null = value;
        if (field === 'reps' || field === 'weight' || field === 'time_seconds' || field === 'distance_meters') {
            parsedValue = value === '' ? null : parseFloat(value);
        }

        setData({
            ...data,
            exercises: data.exercises.map((exercise, exIdx) =>
                exIdx === exerciseIndex
                    ? {
                        ...exercise,
                        sets: exercise.sets.map((set, sIdx) =>
                            sIdx === setIndex
                                ? { ...set, [field]: parsedValue }
                                : set
                        ),
                    }
                    : exercise
            ),
        });
    };

    return (
        <>
            <Head title="Verify Workout" />

            <div className="flex min-h-screen flex-col bg-background">
                {/* Header */}
                <div className="border-b bg-card p-4">
                    <div className="mx-auto max-w-4xl">
                        <h1 className="text-2xl font-bold">Verify Workout Data</h1>
                        <p className="text-sm text-muted-foreground">
                            {data.raw_text ? 'Review manual entry' : 'Review extracted data'} from {data.date}
                        </p>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                    <div className="mx-auto max-w-4xl space-y-6">
                        {/* Success Alert */}
                        <Alert>
                            <Check className="size-4" />
                            <AlertTitle>Ready to Verify</AlertTitle>
                            <AlertDescription>
                                We found {data.exercises.length} exercises. Please review and edit below.
                            </AlertDescription>
                        </Alert>

                        {/* Workout Metadata */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Workout Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="date">Date</Label>
                                        <Input
                                            id="date"
                                            type="date"
                                            value={data.date}
                                            onChange={(e) =>
                                                setData({
                                                    ...data,
                                                    date: e.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            type="text"
                                            value={data.title || ''}
                                            onChange={(e) =>
                                                setData({
                                                    ...data,
                                                    title: e.target.value,
                                                })
                                            }
                                            placeholder="e.g., Chest and Triceps"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="type">Type</Label>
                                        <select
                                            id="type"
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            value={data.type || 'strength'}
                                            onChange={(e) => setData({ ...data, type: e.target.value as Workout['type'] })}
                                        >
                                            <option value="strength">Strength</option>
                                            <option value="crossfit">CrossFit</option>
                                            <option value="cardio">Cardio</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                {/* Photo Reference */}
                                {photoUrl && (
                                    <div className="space-y-2">
                                        <Label>Original Photo</Label>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setShowPhoto(!showPhoto)}
                                            className="w-full"
                                        >
                                            <ImageIcon className="size-4" />
                                            {showPhoto ? 'Hide' : 'Show'} Photo Reference
                                        </Button>
                                        {showPhoto && (
                                            <div className="mt-2 overflow-hidden rounded-lg border">
                                                <img
                                                    src={photoUrl}
                                                    alt="Workout photo"
                                                    className="w-full"
                                                />
                                            </div>
                                        )}
                                    </div>
                                )}

                                {data.raw_text && (
                                    <div className="space-y-2">
                                        <Label>Original Text</Label>
                                        <div className="rounded-md bg-muted p-3 text-sm whitespace-pre-wrap">
                                            {data.raw_text}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Exercises */}
                        {data.exercises.map((exercise, exerciseIndex) => (
                            <Card key={exerciseIndex}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <div className="flex flex-1 items-center gap-2">
                                        <Pencil className="size-4 text-muted-foreground" />
                                        <Input
                                            value={exercise.name}
                                            onChange={(e) =>
                                                updateExerciseName(
                                                    exerciseIndex,
                                                    e.target.value
                                                )
                                            }
                                            className="h-auto border-0 p-0 text-lg font-semibold focus-visible:ring-0"
                                        />
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeExercise(exerciseIndex)}
                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    >
                                        <Trash className="size-4" />
                                    </Button>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {exercise.sets.map((set, setIndex) => (
                                            <div
                                                key={setIndex}
                                                className={`flex items-center gap-3 rounded-lg border p-3 ${set.confidence === 'low'
                                                    ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-950/20'
                                                    : ''
                                                    }`}
                                            >
                                                <span className="min-w-[2rem] text-sm font-medium text-muted-foreground">
                                                    Set {setIndex + 1}
                                                </span>
                                                <div className="flex flex-1 flex-wrap items-center gap-2">
                                                    <Input
                                                        type="number"
                                                        value={set.reps || ''}
                                                        onChange={(e) =>
                                                            updateSet(
                                                                exerciseIndex,
                                                                setIndex,
                                                                'reps',
                                                                e.target.value
                                                            )
                                                        }
                                                        className="w-20"
                                                        placeholder="Reps"
                                                    />
                                                    <span className="text-sm text-muted-foreground">
                                                        ×
                                                    </span>
                                                    <Input
                                                        type="number"
                                                        step="0.5"
                                                        value={set.weight || ''}
                                                        onChange={(e) =>
                                                            updateSet(
                                                                exerciseIndex,
                                                                setIndex,
                                                                'weight',
                                                                e.target.value
                                                            )
                                                        }
                                                        className="w-24"
                                                        placeholder="Weight"
                                                    />
                                                    <span className="min-w-[2rem] text-sm text-muted-foreground">
                                                        {set.unit}
                                                    </span>
                                                    <div className="flex-1"></div>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => removeSet(exerciseIndex, setIndex)}
                                                        className="h-8 w-8 text-muted-foreground hover:text-destructive"
                                                    >
                                                        <X className="size-4" /> {/* Wait X is not imported, use Trash */}
                                                        <Trash className="size-3" />
                                                    </Button>
                                                </div>
                                                {set.confidence === 'low' && (
                                                    <AlertCircle className="size-4 text-yellow-600" />
                                                )}
                                            </div>
                                        ))}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => addSet(exerciseIndex)}
                                            className="w-full border-dashed"
                                        >
                                            <Plus className="mr-2 size-4" />
                                            Add Set
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}

                        <Button
                            variant="outline"
                            size="lg"
                            onClick={addExercise}
                            className="w-full border-dashed py-8"
                        >
                            <Plus className="mr-2 size-4" />
                            Add Exercise
                        </Button>

                        {/* Action Buttons */}
                        <div className="sticky bottom-0 flex gap-3 border-t bg-card p-4">
                            <Button
                                size="lg"
                                onClick={handleSave}
                                disabled={processing}
                                className="flex-1"
                            >
                                {processing ? 'Saving...' : 'Save Workout'}
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                onClick={() => window.location.href = '/workouts/upload'} // better to redirect to upload
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

