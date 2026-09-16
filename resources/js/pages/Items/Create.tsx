import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { store, index as inventoryItemsIndex } from '@/routes/inventory_items';

interface InventoryItemForm {
    item_name: string;
    description: string;
    minimum_stock: string;
}

export default function Create() {
    const { data, setData, post, processing, errors } = useForm<InventoryItemForm>({
        item_name: '',
        description: '',
        minimum_stock: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Item" />

            <div className="mx-auto w-full max-w-lg p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Item</h1>
                 
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-[#f0ddc8] bg-white p-5"
                >
                    <div className="space-y-1.5">
                        <Label htmlFor="item_name" className="font-semibold text-ink">
                            Item Name
                        </Label>
                        <Input
                            id="item_name"
                            value={data.item_name}
                            onChange={(e) => setData('item_name', e.target.value)}
                            maxLength={75}
                            placeholder="e.g gloves"
                            autoFocus
                            className="border-[#e0d0c0]"
                        />
                        {errors.item_name && (
                            <p className="mt-1 text-sm text-danger">{errors.item_name}</p>
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
                            placeholder="Optional notes about this item"
                            rows={3}
                            className="border-[#e0d0c0]"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-danger">{errors.description}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="minimum_stock" className="font-semibold text-ink">
                            Minimum Stock
                        </Label>
                        <Input
                            id="minimum_stock"
                            type="number"
                            value={data.minimum_stock}
                            onChange={(e) => setData('minimum_stock', e.target.value)}
                            placeholder="Optional"
                            className="border-[#e0d0c0]"
                        />
                        {errors.minimum_stock && (
                            <p className="mt-1 text-sm text-danger">{errors.minimum_stock}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-3 border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Create item
                        </Button>
                        <Link
                            href={inventoryItemsIndex().url}
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