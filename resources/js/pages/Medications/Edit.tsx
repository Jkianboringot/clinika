import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update, index as medicationsIndex } from '@/routes/medications';

interface Medication {
    id: number;
    name: string;
    generic_name: string | null;
    dosage_form: string | null;
    strength: string | null;
}

interface EditProps {
    medication: Medication;
}

export default function Edit({ medication }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: medication.name,
        generic_name: medication.generic_name ?? '',
        dosage_form: medication.dosage_form ?? '',
        strength: medication.strength ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(medication.id).url);
    };

    return (
        <>
            <Head title="Edit Medication" />

            <div className="mx-auto max-w-lg p-6">
                <h1 className="mb-6 text-3xl font-extrabold tracking-tight text-ink">
                    Edit medication
                </h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={150}
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
                            Save changes
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

Edit.layout = {
    breadcrumbs: [
        { title: 'Medications', href: '/medications' },
        { title: 'Edit', href: '#' },
    ],
};