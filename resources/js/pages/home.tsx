import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { home } from '@/routes';
import workouts from '@/routes/workouts';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { BarChart3, Camera, Clock, Dumbbell, Flame, Settings, Send, Sparkles } from 'lucide-react';
import React, { type ReactNode } from 'react';

interface RecentWorkout {
    id: number;
    date: string;
    title: string | null;
    total_exercises: number;
    total_volume: number;
}

interface Stats {
    weeklyWorkouts: number;
    totalWorkouts: number;
    daysSinceLastWorkout: number;
    streak: number;
    totalVolume: number;
}

interface Recommendation {
    title: string;
    description: string;
    exercises: { name: string; sets: string; reps: string; notes?: string }[];
}

interface HomeProps {
    recentWorkouts: RecentWorkout[];
    stats: Stats;
    recommendation: Recommendation;
}

export default function Home({ recentWorkouts, stats, recommendation }: HomeProps) {
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
        if (volume >= 1000) {
            return (volume / 1000).toFixed(1) + 'k';
        }
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

    const getFirstName = (name: string) => {
        return name.split(' ')[0] + '.';
    };

    const getWelcomeMessage = () => {
        const days = stats.daysSinceLastWorkout;
        if (days === 0) {
            return "You worked out today. Great job keeping the streak alive!";
        } else if (days === 1) {
            return "You worked out yesterday. Ready for another session?";
        } else if (days > 7) {
            return `It's been ${days} days. Time to get back on track!`;
        } else {
            return `You haven't logged in in ${days} days. Ready to crush some weights?`;
        }
    };

    return (
        <>
            <Head title="Home" />
            <div className="flex min-h-screen flex-col bg-white dark:bg-gray-950 pb-32">
                {/* Header */}
                <header className="flex items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-2">
                        <Flame className="size-5 text-lime-400 fill-lime-400" />
                        <span className="font-bold tracking-tight text-gray-900 dark:text-white">LOG.AI</span>
                    </div>
                    <Avatar className="size-8">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-gray-100 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {getUserInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                </header>

                <main className="px-6">
                    {/* Welcome Section */}
                    <div className="mb-8 mt-2">
                        <h1 className="text-4xl font-black tracking-tight text-lime-500 mb-2">
                            {getFirstName(user.name)}
                        </h1>
                        <p className="text-gray-500 font-medium leading-relaxed dark:text-gray-400 max-w-sm">
                            {getWelcomeMessage()}
                        </p>
                    </div>

                    {/* Stats Row */}
                    <div className="grid grid-cols-3 gap-3 mb-8">
                        <StatCard
                            label="Streak"
                            value={`${stats.streak} Days`}
                            subtext="Keep it up!"
                            icon={<Flame className="size-4 text-lime-500" />}
                            bg="bg-red-50 dark:bg-red-900/10"
                        />
                        <StatCard
                            label="Volume"
                            value={`${formatVolume(stats.totalVolume)}`}
                            subtext="Last 7 days"
                            icon={<BarChart3 className="size-4 text-lime-500" />}
                            bg="bg-lime-50 dark:bg-lime-900/10"
                        />
                        <StatCard
                            label="Workouts"
                            value={stats.totalWorkouts.toString()}
                            subtext="Total logs"
                            icon={<Dumbbell className="size-4 text-lime-500" />}
                            bg="bg-orange-50 dark:bg-orange-900/10"
                        />
                    </div>

                    {/* Recommended Card */}
                    <Card className="mb-8 overflow-hidden border-0 bg-gray-900 text-white shadow-xl dark:bg-gray-800">
                        <CardContent className="p-6 relative">
                            {/* Background accent */}
                            <div className="absolute top-0 right-0 p-6 opacity-10">
                                <Dumbbell className="size-24 rotate-12" />
                            </div>

                            <div className="flex justify-between items-start mb-6 relative z-10">
                                <span className="inline-block rounded bg-lime-500/20 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-lime-400">
                                    Recommended
                                </span>
                                <span className="text-xs font-medium text-gray-400">
                                    Today, 5:00 PM
                                </span>
                            </div>

                            <h3 className="text-2xl font-black mb-2 relative z-10">
                                {recommendation.title}
                            </h3>

                            <p className="text-gray-400 text-sm mb-6 leading-relaxed relative z-10">
                                {recommendation.description}
                            </p>

                            {/* Exercise List */}
                            {recommendation.exercises && recommendation.exercises.length > 0 && (
                                <div className="relative z-10 space-y-2 mb-6">
                                    {recommendation.exercises.map((exercise, index) => (
                                        <div key={index} className="flex items-start gap-3 bg-gray-800/50 rounded-lg p-3">
                                            <div className="flex-shrink-0 size-6 rounded-full bg-lime-500/20 flex items-center justify-center text-[10px] font-bold text-lime-400">
                                                {index + 1}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 mb-1">
                                                    <span className="font-bold text-sm text-white">{exercise.name}</span>
                                                    <span className="text-xs text-gray-500">{exercise.sets} × {exercise.reps}</span>
                                                </div>
                                                <p className="text-xs text-gray-400 leading-relaxed">{exercise.notes}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="flex items-center justify-between relative z-10">
                                <Link href={workouts.upload().url}>
                                    <Button className="bg-white text-gray-900 hover:bg-gray-100 font-bold rounded-xl px-6">
                                        Start Log
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Activity */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-gray-500">
                                Recent Activity
                            </h3>
                            <Link href={workouts.index().url} className="text-xs font-bold text-lime-500 hover:text-lime-600">
                                View All
                            </Link>
                        </div>

                        <div className="space-y-3 mb-20">
                            {recentWorkouts.map((workout) => (
                                <Link key={workout.id} href={workouts.show(workout.id).url}>
                                    <Card className="border-none shadow-sm bg-gray-50 dark:bg-gray-900/50">
                                        <CardContent className="flex items-center p-4">
                                            <div className="mr-4 flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-white text-xs font-bold shadow-sm dark:bg-gray-800">
                                                <span className="text-lime-500 uppercase">{formatDate(workout.date).month}</span>
                                                <span className="text-lg text-gray-900 dark:text-white">{formatDate(workout.date).day}</span>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h4 className="font-bold text-gray-900 truncate dark:text-white text-base">
                                                    {workout.title || 'Workout'}
                                                </h4>
                                                <div className="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                                    <Dumbbell className="size-3" />
                                                    <span>{workout.total_exercises} Exercises</span>
                                                    <span className="text-gray-300">•</span>
                                                    <span>{formatVolume(workout.total_volume)}kg</span>
                                                </div>
                                            </div>
                                            <div className="text-gray-400">
                                                <svg width="6" height="10" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 9L5 5L1 1" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                                </svg>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                            {recentWorkouts.length === 0 && (
                                <div className="text-center py-8 text-gray-400">
                                    No recent activity
                                </div>
                            )}
                        </div>
                    </div>
                </main>

                <AskAIBar recommendation={recommendation} />
                <BottomNavigation />
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    subtext,
    icon,
    bg = "bg-white dark:bg-gray-900"
}: {
    label: string;
    value: string;
    subtext?: string;
    icon: ReactNode;
    bg?: string;
}) {
    return (
        <div className={`flex flex-col p-4 rounded-3xl ${bg} shadow-sm border border-gray-100 dark:border-gray-800`}>
            <div className="mb-3 size-8 rounded-full bg-gray-50/50 dark:bg-white/10 flex items-center justify-center backdrop-blur-sm">
                {icon}
            </div>
            <span className="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">{label}</span>
            <span className="text-xl font-black text-gray-900 dark:text-white mb-0.5 leading-none">{value}</span>
            {subtext && <span className="text-[10px] text-gray-400 font-medium">{subtext}</span>}
        </div>
    );
}

function AskAIBar({ recommendation }: { recommendation?: Recommendation }) {
    const [query, setQuery] = React.useState('');
    const [loading, setLoading] = React.useState(false);

    const handleAsk = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!query.trim()) return;

        setLoading(true);
        try {
            const res = await axios.post('/ai/chat', {
                message: query,
                context: recommendation
            });
            alert(res.data.response); // Simple alert for now, can be improved to a modal later
            setQuery('');
        } catch (error) {
            console.error('AI Chat Error:', error);
            alert('Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleAsk} className="fixed bottom-24 left-6 right-6 z-40">
            <div className="bg-gray-900 dark:bg-gray-800 rounded-full py-3 px-5 flex items-center shadow-lg border border-gray-800">
                <div className="size-8 rounded-full bg-lime-500/20 flex items-center justify-center mr-3">
                    <Sparkles className="size-4 text-lime-400" />
                </div>
                <input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={loading ? "Thinking..." : "Ask AI or log workout..."}
                    disabled={loading}
                    className="flex-1 bg-transparent border-none text-white placeholder-gray-500 text-sm focus:ring-0 px-0"
                />
                <button
                    type="submit"
                    disabled={loading}
                    className="bg-white rounded-full p-2 size-8 flex items-center justify-center disabled:opacity-50"
                >
                    <Send className="size-4 text-gray-900 ml-0.5" />
                </button>
            </div>
        </form>
    )
}

function BottomNavigation() {
    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 bg-gray-900 text-white rounded-t-[2rem] shadow-2xl px-6 pb-6 pt-4">
            <div className="flex items-center justify-between relative">
                <NavItem href={workouts.index().url} icon={<Clock className="size-5" />} label="History" />
                <NavItem href={home().url} icon={<Dumbbell className="size-5" />} label="Today" active />

                {/* Spacer for Camera Button */}
                <div className="w-12 h-12"></div>

                <NavItem href="/stats" icon={<BarChart3 className="size-5" />} label="Stats" />
                <NavItem href="/settings" icon={<Settings className="size-5" />} label="Settings" />

                {/* Floating Camera Button */}
                <div className="absolute left-1/2 -translate-x-1/2 -top-10">
                    <Link href={workouts.upload().url}>
                        <div className="size-16 rounded-full bg-lime-400 flex items-center justify-center shadow-[0_0_20px_rgba(163,230,53,0.4)] border-[6px] border-white dark:border-gray-950 transition-transform hover:scale-105 active:scale-95">
                            <Camera className="size-7 text-gray-900" strokeWidth={2.5} />
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    );
}

function NavItem({ href, icon, label, active = false }: { href: string; icon: ReactNode; label: string; active?: boolean }) {
    return (
        <Link
            href={href}
            className={`flex flex-col items-center gap-1 transition-colors ${active
                ? 'text-lime-400'
                : 'text-gray-500 hover:text-gray-300'
                }`}
        >
            <div className={active ? '' : ''}>
                {icon}
            </div>
            <span className="text-[10px] font-bold uppercase tracking-wide">{label}</span>
        </Link>
    );
}
