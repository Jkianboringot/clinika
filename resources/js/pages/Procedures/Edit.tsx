import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update, index as proceduresIndex } from '@/routes/procedures';

interface Procedure {
    id: number;
    name: string;
}

interface EditProps {
    procedure: Procedure;
}

export default function Edit({ procedure }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: procedure.name,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(procedure.id).url);
    };

    return (
        <>
            <Head title="Edit Procedure" />

            <div className="mx-auto max-w-lg p-6">
                <h1 className="mb-6 text-3xl font-extrabold tracking-tight text-ink">
                    Edit procedure
                </h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={100}
                            autoFocus
                        />
                        {errors.name && (
                            <p className="text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                        >
                            Save changes
                        </Button>
                        <Link
                            href={proceduresIndex().url}
                            className="text-sm font-medium text-subtle hover:text-ink"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Procedures', href: '/procedures' },
        { title: 'Edit', href: '#' },
    ],
};