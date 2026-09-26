import type { HTMLAttributes } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    // Appearance is locked to 'light' — this component now renders
    // nothing. Kept only so existing imports don't break; safe to
    // delete both this file and its usages once cleaned up.
    useAppearance();

    return null;
}