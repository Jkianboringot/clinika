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

            <div className="mx-auto w-full max-w-lg p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Medication</h1>
                   
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-[#f0ddc8] bg-white p-5"
                >
                    <div className="space-y-1.5">
                        <Label htmlFor="name" className="font-semibold text-ink">
                            Name
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={150}
                            autoFocus
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="generic_name" className="font-semibold text-ink">
                            Generic name
                        </Label>
                        <Input
                            id="generic_name"
                            value={data.generic_name}
                            onChange={(e) => setData('generic_name', e.target.value)}
                            maxLength={150}
                            className="border-[#e0d0c0]"
                        />
                        {errors.generic_name && (
                            <p className="mt-1 text-sm text-danger">{errors.generic_name}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="dosage_form" className="font-semibold text-ink">
                                Dosage form
                            </Label>
                            <Input
                                id="dosage_form"
                                value={data.dosage_form}
                                onChange={(e) => setData('dosage_form', e.target.value)}
                                maxLength={75}
                                className="border-[#e0d0c0]"
                            />
                            {errors.dosage_form && (
                                <p className="mt-1 text-sm text-danger">{errors.dosage_form}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="strength" className="font-semibold text-ink">
                                Strength
                            </Label>
                            <Input
                                id="strength"
                                value={data.strength}
                                onChange={(e) => setData('strength', e.target.value)}
                                maxLength={50}
                                className="border-[#e0d0c0]"
                            />
                            {errors.strength && (
                                <p className="mt-1 text-sm text-danger">{errors.strength}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-3 border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
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