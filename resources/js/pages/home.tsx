import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { home } from '@/routes';
import workouts from '@/routes/workouts';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, Camera, Clock, Dumbbell, Settings, Zap } from 'lucide-react';
import { type ReactNode } from 'react';

interface RecentWorkout {
    id: number;
    date: string;
    title: string | null;
    total_exercises: number;
    total_volume: number;
}

interface Stats {
    weeklyWorkouts: number;
    streak: number;
    totalVolume: number;
}

interface HomeProps {
    recentWorkouts: RecentWorkout[];
    stats: Stats;
}

export default function Home({ recentWorkouts, stats }: HomeProps) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return {
            day: new Intl.DateTimeFormat('en-US', { day: '2-digit' }).format(date),
            month: new Intl.DateTimeFormat('en-US', { month: 'short' }).format(date).toUpperCase(),
        };
    };

    const formatVolume = (volume: number) => {
        return new Intl.NumberFormat('en-US').format(Math.round(volume));
    };

    const getUserInitials = (name: string) => {
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <>
            <Head title="Home" />
            <div className="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-900">
                {/* Header */}
                <header className="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-950">
                    <div>
                        <div className="flex items-center gap-2">
                            <Zap className="size-6 fill-lime-400 text-lime-400" />
                            <h1 className="text-xl font-black tracking-tight">LOG.AI</h1>
                        </div>
                        <p className="text-xs font-medium uppercase tracking-widest text-gray-500">
                            Digital Logbook
                        </p>
                    </div>
                    <Avatar>
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {getUserInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                </header>

                {/* Main Content */}
                <main className="flex-1 overflow-y-auto pb-20">
                    <div className="mx-auto max-w-2xl px-4 py-6">
                        {/* Hero Section - Scan Log CTA */}
                        <div className="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 p-8 shadow-lg dark:from-gray-800 dark:to-gray-900">
                            {/* Background Pattern */}
                            <div className="absolute inset-0 opacity-10">
                                <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iYmxhY2siIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')]" />
                            </div>

                            {/* Content */}
                            <div className="relative flex flex-col items-center justify-center space-y-4">
                                <Link href={workouts.upload().url}>
                                    <Button
                                        size="lg"
                                        className="group relative size-32 rounded-full bg-lime-400 p-0 shadow-2xl transition-all hover:scale-105 hover:bg-lime-500 hover:shadow-lime-500/50 active:scale-95 dark:bg-lime-500 dark:hover:bg-lime-600"
                                    >
                                        <div className="absolute inset-0 animate-pulse rounded-full bg-lime-400/50 dark:bg-lime-500/50" />
                                        <Camera className="relative size-12 text-gray-900" strokeWidth={2.5} />
                                    </Button>
                                </Link>
                                <div className="text-center">
                                    <h2 className="text-3xl font-black uppercase tracking-tight text-gray-900 dark:text-gray-100">
                                        Scan Log
                                    </h2>
                                    <p className="mt-1 text-sm font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                        Capture Workout Data
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Stats Dashboard */}
                        <div className="mb-6 grid grid-cols-3 gap-3">
                            <StatCard
                                icon={<Dumbbell className="size-4 text-lime-500" />}
                                label="This Week"
                                value={stats.weeklyWorkouts.toString()}
                            />
                            <StatCard
                                icon={<Zap className="size-4 fill-lime-500 text-lime-500" />}
                                label="Streak"
                                value={stats.streak.toString()}
                            />
                            <StatCard
                                icon={<BarChart3 className="size-4 text-lime-500" />}
                                label="Volume"
                                value={`${formatVolume(stats.totalVolume)}KG`}
                            />
                        </div>

                        {/* Recent Logs Section */}
                        <div className="mb-6">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-bold uppercase tracking-tight text-gray-900 dark:text-gray-100">
                                    Recent Logs
                                </h3>
                                <Link href={workouts.index().url}>
                                    <Button variant="ghost" size="sm" className="text-xs font-semibold uppercase text-lime-600 hover:text-lime-700 dark:text-lime-500 dark:hover:text-lime-400">
                                        View All
                                    </Button>
                                </Link>
                            </div>

                            {/* Workout Cards */}
                            {recentWorkouts.length === 0 ? (
                                <Card className="bg-white dark:bg-gray-900">
                                    <CardContent className="flex flex-col items-center justify-center py-12">
                                        <Dumbbell className="mb-4 size-12 text-gray-400" />
                                        <h4 className="mb-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                                            No workouts yet
                                        </h4>
                                        <p className="mb-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                            Scan your first workout photo to get started!
                                        </p>
                                    </CardContent>
                                </Card>
                            ) : (
                                <div className="space-y-3">
                                    {recentWorkouts.map((workout) => {
                                        const { day, month } = formatDate(workout.date);
                                        return (
                                            <Link
                                                key={workout.id}
                                                href={workouts.show(workout.id).url}
                                            >
                                                <Card className="overflow-hidden bg-white transition-all hover:shadow-md dark:bg-gray-900">
                                                    <CardContent className="flex items-center gap-4 p-4">
                                                        {/* Date Badge */}
                                                        <div className="flex size-16 shrink-0 flex-col items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                                            <div className="text-2xl font-black leading-none text-gray-900 dark:text-gray-100">
                                                                {day}
                                                            </div>
                                                            <div className="text-xs font-bold leading-none text-gray-600 dark:text-gray-400">
                                                                {month}
                                                            </div>
                                                        </div>

                                                        {/* Workout Info */}
                                                        <div className="flex-1">
                                                            <h4 className="mb-1 text-base font-bold uppercase leading-tight text-gray-900 dark:text-gray-100">
                                                                {workout.title || 'Workout'}
                                                            </h4>
                                                            <div className="flex items-center gap-4 text-xs font-medium text-gray-600 dark:text-gray-400">
                                                                <span className="flex items-center gap-1">
                                                                    <Dumbbell className="size-3" />
                                                                    {workout.total_exercises} Exercise{workout.total_exercises !== 1 ? 's' : ''}
                                                                </span>
                                                                <span className="font-bold text-gray-900 dark:text-gray-100">
                                                                    {formatVolume(workout.total_volume)}kg
                                                                </span>
                                                            </div>
                                                        </div>

                                                        {/* Arrow */}
                                                        <div className="shrink-0 text-gray-400">
                                                            <svg
                                                                width="20"
                                                                height="20"
                                                                viewBox="0 0 20 20"
                                                                fill="none"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                            >
                                                                <path
                                                                    d="M7.5 15L12.5 10L7.5 5"
                                                                    stroke="currentColor"
                                                                    strokeWidth="2"
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                />
                                                            </svg>
                                                        </div>
                                                    </CardContent>
                                                </Card>
                                            </Link>
                                        );
                                    })}

                                    {/* Add Workout Button */}
                                    <Link href={workouts.upload().url}>
                                        <Card className="overflow-hidden border-2 border-dashed border-gray-300 bg-transparent transition-all hover:border-lime-500 hover:bg-lime-50/50 dark:border-gray-700 dark:hover:border-lime-500 dark:hover:bg-lime-950/20">
                                            <CardContent className="flex items-center justify-center gap-2 p-6">
                                                <Camera className="size-5 text-gray-500 dark:text-gray-400" />
                                                <span className="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                                    Add New Workout
                                                </span>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </main>

                {/* Bottom Navigation */}
                <BottomNavigation />
            </div>
        </>
    );
}

// Stats Card Component
function StatCard({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
    return (
        <Card className="bg-white shadow-sm dark:bg-gray-900">
            <CardContent className="flex flex-col items-center justify-center p-4">
                <div className="mb-2">{icon}</div>
                <div className="mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {label}
                </div>
                <div className="text-2xl font-black text-gray-900 dark:text-gray-100">
                    {value}
                </div>
            </CardContent>
        </Card>
    );
}

// Bottom Navigation Component
function BottomNavigation() {
    return (
        <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-950">
            <div className="mx-auto flex max-w-2xl items-center justify-around px-4 py-3">
                <NavItem href={workouts.index().url} icon={<Clock className="size-5" />} label="History" />
                <NavItem href={home().url} icon={<Dumbbell className="size-5" />} label="Today" active />
                <NavItem href="/stats" icon={<BarChart3 className="size-5" />} label="Stats" />
                <NavItem href="/settings" icon={<Settings className="size-5" />} label="Settings" />
            </div>
        </nav>
    );
}

// Nav Item Component
function NavItem({ href, icon, label, active = false }: { href: string; icon: ReactNode; label: string; active?: boolean }) {
    return (
        <Link
            href={href}
            className={`flex flex-col items-center gap-1 transition-colors ${
                active
                    ? 'text-lime-600 dark:text-lime-500'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
            }`}
        >
            <div className={active ? 'rounded-full bg-lime-100 p-1.5 dark:bg-lime-950' : ''}>
                {icon}
            </div>
            <span className="text-[10px] font-bold uppercase tracking-wide">{label}</span>
        </Link>
    );
}

