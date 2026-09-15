import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { store, index as proceduresIndex } from '@/routes/procedures';

interface ProcedureForm {
    name: string;
    description: string;
    duration: string;
    price: string;
    is_active: boolean;
}

export default function Create() {
    const { data, setData, post, processing, errors } = useForm<ProcedureForm>({
        name: '',
        description: '',
        duration: '',
        price: '',
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Procedure" />

            <div className="mx-auto w-full max-w-lg p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Procedure</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Add a procedure to your catalog.
                    </p>
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
                            maxLength={100}
                            placeholder="e.g. Root Canal Therapy"
                            autoFocus
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="description" className="font-semibold text-ink">
                            Description
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Optional notes about this procedure"
                            rows={3}
                            className="border-[#e0d0c0]"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-danger">{errors.description}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="duration" className="font-semibold text-ink">
                                Duration (minutes)
                            </Label>
                            <Input
                                id="duration"
                                type="number"
                                min={0}
                                value={data.duration}
                                onChange={(e) => setData('duration', e.target.value)}
                                placeholder="Optional"
                                className="border-[#e0d0c0]"
                            />
                            {errors.duration && (
                                <p className="mt-1 text-sm text-danger">{errors.duration}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="price" className="font-semibold text-ink">
                                Price
                            </Label>
                            <Input
                                id="price"
                                type="number"
                                min={0}
                                step="0.01"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                                placeholder="0.00"
                                className="border-[#e0d0c0]"
                            />
                            {errors.price && (
                                <p className="mt-1 text-sm text-danger">{errors.price}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center justify-between rounded-lg border border-[#e0d0c0] px-3 py-2.5">
                        <div>
                            <Label htmlFor="is_active" className="font-semibold text-ink">
                                Active
                            </Label>
                            <p className="text-xs text-subtle">
                                Inactive procedures won't be offered for new appointments.
                            </p>
                        </div>
                        <Switch
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData('is_active', checked)}
                        />
                        {errors.is_active && (
                            <p className="mt-1 text-sm text-danger">{errors.is_active}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-3 border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Create procedure
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

Create.layout = {
    breadcrumbs: [
        { title: 'Procedures', href: '/procedures' },
        { title: 'New', href: '/procedures/create' },
    ],
};