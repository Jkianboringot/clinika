import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light';
export type Appearance = ResolvedAppearance;

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const applyTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

export function initializeTheme(): void {
    applyTheme();
}

export function useAppearance(): UseAppearanceReturn {
    // No external store needed anymore — appearance never changes,
    // so this is just a stable constant, no re-renders will ever fire.
    const appearance: Appearance = useSyncExternalStore(
        () => () => {}, // subscribe: no-op, nothing to listen for
        () => 'light',
        () => 'light',
    );

    const updateAppearance = (_mode: Appearance): void => {
        // Intentionally a no-op — appearance is locked to light.
        applyTheme();
    };

    return { appearance, resolvedAppearance: appearance, updateAppearance } as const;
}