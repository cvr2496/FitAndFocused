import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Workout } from '@/types/workout';
import { Head, useForm } from '@inertiajs/react';
import { AlertCircle, Check, Image as ImageIcon, Pencil } from 'lucide-react';
import { useState } from 'react';

interface VerifyWorkoutProps {
    workout: Workout;
    photoUrl: string;
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

    const updateSet = (
        exerciseIndex: number,
        setIndex: number,
        field: 'reps' | 'weight',
        value: string
    ) => {
        const numValue = value === '' ? null : parseFloat(value);
        
        setData({
            ...data,
            exercises: data.exercises.map((exercise, exIdx) =>
                exIdx === exerciseIndex
                    ? {
                          ...exercise,
                          sets: exercise.sets.map((set, sIdx) =>
                              sIdx === setIndex
                                  ? { ...set, [field]: numValue }
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
                            Review and correct the extracted data before saving
                        </p>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                    <div className="mx-auto max-w-4xl space-y-6">
                        {/* Success Alert */}
                        <Alert>
                            <Check className="size-4" />
                            <AlertTitle>Extraction Complete</AlertTitle>
                            <AlertDescription>
                                Successfully extracted {data.exercises.length} exercises
                                with{' '}
                                {data.exercises.reduce(
                                    (total, ex) => total + ex.sets.length,
                                    0
                                )}{' '}
                                total sets. Review the data below and make any corrections.
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
                                </div>

                                {/* Photo Reference */}
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
                            </CardContent>
                        </Card>

                        {/* Exercises */}
                        {data.exercises.map((exercise, exerciseIndex) => (
                            <Card key={exerciseIndex}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Pencil className="size-4 text-muted-foreground" />
                                        <Input
                                            value={exercise.name}
                                            onChange={(e) =>
                                                updateExerciseName(
                                                    exerciseIndex,
                                                    e.target.value
                                                )
                                            }
                                            className="border-0 p-0 text-lg font-semibold focus-visible:ring-0"
                                        />
                                    </CardTitle>
                                    <CardDescription>
                                        {exercise.sets.length} set
                                        {exercise.sets.length !== 1 ? 's' : ''}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {exercise.sets.map((set, setIndex) => (
                                            <div
                                                key={setIndex}
                                                className={`flex items-center gap-3 rounded-lg border p-3 ${
                                                    set.confidence === 'low'
                                                        ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-950/20'
                                                        : ''
                                                }`}
                                            >
                                                <span className="min-w-[2rem] text-sm font-medium text-muted-foreground">
                                                    Set {setIndex + 1}
                                                </span>
                                                <div className="flex flex-1 items-center gap-2">
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
                                                </div>
                                                {set.confidence === 'low' && (
                                                    <AlertCircle className="size-4 text-yellow-600" />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}

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
                                onClick={() => window.location.href = '/test-upload'}
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

