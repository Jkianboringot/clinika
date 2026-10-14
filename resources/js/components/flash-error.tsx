import { usePage } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';

interface FlashProps {
    flash?: { error?: string | null };
}

export default function FlashError() {
    const { flash } = usePage<FlashProps & Record<string, unknown>>()
        .props as unknown as FlashProps;

    if (!flash?.error) return null;

    return (
        <div
            role="alert"
            className="mb-5 flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3"
        >
            <AlertCircle size={18} className="mt-0.5 shrink-0 text-red-500" />
            <p className="text-sm font-medium text-red-600">{flash.error}</p>
        </div>
    );
}