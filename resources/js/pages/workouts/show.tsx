import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import workouts from '@/routes/workouts';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Dumbbell, Image as ImageIcon, StickyNote, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface SetData {
    id: number;
    set_number: number;
    reps: number | null;
    weight: number | null;
    unit: string;
    notes: string | null;
}

interface Exercise {
    name: string;
    sets: SetData[];
}

interface Workout {
    id: number;
    date: string;
    title: string | null;
    photo_path: string | null;
    photo_url: string | null;
    notes: string | null;
    created_at: string;
}

interface WorkoutShowProps {
    workout: Workout;
    exercises: Exercise[];
}

export default function WorkoutShow({ workout, exercises }: WorkoutShowProps) {
    const [showPhoto, setShowPhoto] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        }).format(date);
    };

    const totalSets = exercises.reduce((sum, ex) => sum + ex.sets.length, 0);

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/workouts/${workout.id}`, {
            onSuccess: () => {
                // Redirect happens automatically via Laravel
            },
            onError: () => {
                setIsDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    return (
        <AppLayout>
            <Head title={`Workout - ${workout.title || formatDate(workout.date)}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Back Button */}
                    <Button variant="ghost" asChild className="mb-4">
                        <Link href={workouts.index().url}>
                            <ArrowLeft className="mr-2 size-4" />
                            Back to Workouts
                        </Link>
                    </Button>

                    {/* Workout Header */}
                    <div className="mb-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Calendar className="size-4" />
                                    <span className="text-sm">{formatDate(workout.date)}</span>
                                </div>
                                <h1 className="mt-1 text-3xl font-bold">
                                    {workout.title || 'Workout'}
                                </h1>
                                <div className="mt-2 flex gap-4 text-sm text-muted-foreground">
                                    <span>
                                        <strong className="font-semibold text-foreground">
                                            {exercises.length}
                                        </strong>{' '}
                                        exercise{exercises.length !== 1 ? 's' : ''}
                                    </span>
                                    <span>•</span>
                                    <span>
                                        <strong className="font-semibold text-foreground">
                                            {totalSets}
                                        </strong>{' '}
                                        set{totalSets !== 1 ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                            <Button
                                variant="destructive"
                                onClick={() => setShowDeleteDialog(true)}
                            >
                                <Trash2 className="mr-2 size-4" />
                                Delete
                            </Button>
                        </div>
                    </div>

                    {/* Workout Notes */}
                    {workout.notes && (
                        <Alert className="mb-6">
                            <StickyNote className="size-4" />
                            <AlertDescription>{workout.notes}</AlertDescription>
                        </Alert>
                    )}

                    {/* Photo Reference */}
                    {workout.photo_url && (
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ImageIcon className="size-4" />
                                    Original Photo
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowPhoto(!showPhoto)}
                                    className="w-full"
                                >
                                    {showPhoto ? 'Hide' : 'Show'} Photo
                                </Button>
                                {showPhoto && (
                                    <div className="mt-4 overflow-hidden rounded-lg border">
                                        <img
                                            src={workout.photo_url}
                                            alt="Workout photo"
                                            className="w-full"
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Exercises */}
                    <div className="space-y-6">
                        {exercises.map((exercise, index) => (
                            <Card key={index}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Dumbbell className="size-5" />
                                        {exercise.name}
                                    </CardTitle>
                                    <CardDescription>
                                        {exercise.sets.length} set{exercise.sets.length !== 1 ? 's' : ''}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-16">Set</TableHead>
                                                <TableHead>Reps</TableHead>
                                                <TableHead>Weight</TableHead>
                                                <TableHead>Notes</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {exercise.sets.map((set, setIndex) => (
                                                <TableRow key={set.id}>
                                                    <TableCell className="font-medium">
                                                        {setIndex + 1}
                                                    </TableCell>
                                                    <TableCell>
                                                        {set.reps ?? '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {set.weight
                                                            ? `${set.weight} ${set.unit}`
                                                            : '-'}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {set.notes || '-'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Workout</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this workout? This action cannot be undone and will also delete all associated sets and exercises.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDeleteDialog(false)}
                            disabled={isDeleting}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={isDeleting}
                        >
                            {isDeleting ? 'Deleting...' : 'Delete Workout'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

