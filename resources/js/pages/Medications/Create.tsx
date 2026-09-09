import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, index as medicationsIndex } from '@/routes/medications';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        generic_name: '',
        dosage_form: '',
        strength: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Medication" />

            <div className="mx-auto max-w-lg p-6">
                <h1 className="mb-6 text-3xl font-extrabold tracking-tight text-ink">
                    New medication
                </h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={150}
                            placeholder="e.g. Amoxicillin"
                            autoFocus
                        />
                        {errors.name && <p className="text-sm text-danger">{errors.name}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="generic_name">Generic name</Label>
                        <Input
                            id="generic_name"
                            value={data.generic_name}
                            onChange={(e) => setData('generic_name', e.target.value)}
                            maxLength={150}
                            placeholder="Optional"
                        />
                        {errors.generic_name && (
                            <p className="text-sm text-danger">{errors.generic_name}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="dosage_form">Dosage form</Label>
                            <Input
                                id="dosage_form"
                                value={data.dosage_form}
                                onChange={(e) => setData('dosage_form', e.target.value)}
                                maxLength={75}
                                placeholder="e.g. Tablet"
                            />
                            {errors.dosage_form && (
                                <p className="text-sm text-danger">{errors.dosage_form}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="strength">Strength</Label>
                            <Input
                                id="strength"
                                value={data.strength}
                                onChange={(e) => setData('strength', e.target.value)}
                                maxLength={50}
                                placeholder="e.g. 500mg"
                            />
                            {errors.strength && (
                                <p className="text-sm text-danger">{errors.strength}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                        >
                            Create medication
                        </Button>
                        <Link
                            href={medicationsIndex().url}
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

Create.layout = {
    breadcrumbs: [
        { title: 'Medications', href: '/medications' },
        { title: 'New', href: '/medications/create' },
    ],
};