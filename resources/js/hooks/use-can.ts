import { usePage } from '@inertiajs/react';

export function useCan() {
    const { permissions } = usePage<{ permissions: string[] }>().props;
    return (permission: string) => permissions.includes(permission);
}