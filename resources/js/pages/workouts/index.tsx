import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import workoutRoutes from '@/routes/workouts';
import { Head, Link } from '@inertiajs/react';
import { Calendar, Dumbbell, Image as ImageIcon, Plus } from 'lucide-react';

interface WorkoutSummary {
    id: number;
    date: string;
    title: string | null;
    photo_path: string | null;
    notes: string | null;
    total_sets: number;
    total_exercises: number;
    created_at: string;
}

interface WorkoutsIndexProps {
    workouts: WorkoutSummary[];
}

export default function WorkoutsIndex({ workouts }: WorkoutsIndexProps) {
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(date);
    };

    return (
        <AppLayout>
            <Head title="My Workouts" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">My Workouts</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {workouts.length} workout{workouts.length !== 1 ? 's' : ''} logged
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={workoutRoutes.upload().url}>
                                <Plus className="mr-2 size-4" />
                                Upload Workout
                            </Link>
                        </Button>
                    </div>

                    {/* Workouts List */}
                    {workouts.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12">
                                <Dumbbell className="mb-4 size-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">No workouts yet</h3>
                                <p className="mb-4 text-center text-sm text-muted-foreground">
                                    Upload your first workout photo to get started!
                                </p>
                                <Button asChild>
                                    <Link href={workoutRoutes.upload().url}>
                                        <Plus className="mr-2 size-4" />
                                        Upload Workout
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="space-y-4">
                            {workouts.map((workout) => (
                                <Link
                                    key={workout.id}
                                    href={workoutRoutes.show(workout.id).url}
                                >
                                    <Card className="transition-colors hover:bg-accent">
                                        <CardHeader>
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <CardTitle className="flex items-center gap-2">
                                                        <Calendar className="size-4 text-muted-foreground" />
                                                        {formatDate(workout.date)}
                                                    </CardTitle>
                                                    {workout.title && (
                                                        <CardDescription className="mt-1 text-base font-medium">
                                                            {workout.title}
                                                        </CardDescription>
                                                    )}
                                                </div>
                                                {workout.photo_path && (
                                                    <ImageIcon className="size-5 text-muted-foreground" />
                                                )}
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="flex gap-4 text-sm text-muted-foreground">
                                                <span>
                                                    <strong className="font-semibold text-foreground">
                                                        {workout.total_exercises}
                                                    </strong>{' '}
                                                    exercise{workout.total_exercises !== 1 ? 's' : ''}
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    <strong className="font-semibold text-foreground">
                                                        {workout.total_sets}
                                                    </strong>{' '}
                                                    set{workout.total_sets !== 1 ? 's' : ''}
                                                </span>
                                            </div>
                                            {workout.notes && (
                                                <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                                    {workout.notes}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

