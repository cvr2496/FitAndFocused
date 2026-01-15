import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'dark';

const applyTheme = () => {
    // Force dark mode always
    document.documentElement.classList.add('dark');
    document.documentElement.style.colorScheme = 'dark';
};

export function initializeTheme() {
    // Always apply dark theme
    applyTheme();
}

export function useAppearance() {
    const [appearance] = useState<Appearance>('dark');

    const updateAppearance = useCallback(() => {
        // Force dark mode - no-op for compatibility
        applyTheme();
    }, []);

    useEffect(() => {
        // Ensure dark mode on mount
        applyTheme();
    }, []);

    return { appearance, updateAppearance } as const;
}
