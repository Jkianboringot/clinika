import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update, index as medicationsIndex } from '@/routes/medications';

interface Medication {
    id: number;
    item_name: string;
    minumun_stock: number | null;
}

interface EditProps {
    item: Medication;
}

export default function Edit({ item }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        item_name: item.item_name,
        minumun_stock: item.minumun_stock ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(item.id).url);
    };

    return (
        <>
            <Head title="Edit Medication" />

            <div className="mx-auto max-w-lg p-6">
                <h1 className="mb-6 text-3xl font-extrabold tracking-tight text-ink">
                    Edit item
                </h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="item_name">Name</Label>
                        <Input
                            id="item_name"
                            value={data.item_name}
                            onChange={(e) => setData('item_name', e.target.value)}
                            maxLength={150}
                            autoFocus
                        />
                        {errors.item_name && <p className="text-sm text-danger">{errors.item_name}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="minumun_stock">Generic item_name</Label>
                        <Input
                            id="minumun_stock"
                            value={data.minumun_stock}
                            onChange={(e) => setData('minumun_stock', e.target.value)}
                            maxLength={150}
                        />
                        {errors.minumun_stock && (
                            <p className="text-sm text-danger">{errors.minumun_stock}</p>
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
        { title: 'Items', href: '/inventory_items' },
        { title: 'Edit', href: '/inventory_items/edit' },
    ],
};