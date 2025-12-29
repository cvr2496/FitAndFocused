import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Moon } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance } = useAppearance();

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-card p-1 border border-border',
                className,
            )}
            {...props}
        >
            <button
                disabled
                className={cn(
                    'flex items-center rounded-md px-3.5 py-1.5 transition-colors cursor-not-allowed',
                    'bg-primary text-primary-foreground shadow-xs',
                )}
            >
                <Moon className="-ml-1 h-4 w-4" />
                <span className="ml-1.5 text-sm font-medium">Dark Mode</span>
            </button>
            <div className="flex items-center px-3 text-xs text-muted-foreground">
                Gym logbook aesthetic • Always on
            </div>
        </div>
    );
}
