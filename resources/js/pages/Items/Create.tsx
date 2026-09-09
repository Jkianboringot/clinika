import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, index as medicationsIndex } from '@/routes/medications';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        item_name: '',
        minumun_stock: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Item" />

            <div className="mx-auto max-w-lg p-6">
                <h1 className="mb-6 text-3xl font-extrabold tracking-tight text-ink">
                    New item
                </h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor="item_name">Item_name</Label>
                        <Input
                            id="item_name"
                            value={data.item_name}
                            onChange={(e) => setData('item_name', e.target.value)}
                            maxLength={150}
                            placeholder="e.g. Amoxicillin"
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
                            placeholder="Optional"
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
                            Create item
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
        { title: 'Item', href: '/inventory_items' },
        { title: 'New', href: '/inventory_items/create' },
    ],
};