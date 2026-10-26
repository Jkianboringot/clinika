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
    critical_stock: string;
}

export default function Create() {
    const { data, setData, post, processing, errors } = useForm<InventoryItemForm>({
        item_name: '',
        description: '',
        minimum_stock: '',
        critical_stock: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Item" />

            <div className="mx-auto w-full max-w-2xl p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">New Item</h1>
                    <p className="mt-1 text-sm text-subtle">Add a supply to the clinic inventory</p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-6 rounded-2xl border border-[#f0ddc8] bg-white p-6 shadow-sm"
                >
                    {/* Name */}
                    <div className="space-y-1.5">
                        <Label htmlFor="item_name" className="text-sm font-medium text-ink">
                            Item name
                        </Label>
                        <Input
                            id="item_name"
                            value={data.item_name}
                            onChange={(e) => setData('item_name', e.target.value)}
                            maxLength={75}
                            placeholder="e.g. Gloves"
                            autoFocus
                            className="h-10 border-[#e0d0c0]"
                        />
                        {errors.item_name && (
                            <p className="text-sm text-danger">{errors.item_name}</p>
                        )}
                    </div>

                    {/* Stock thresholds */}
                    <div className="rounded-2xl border border-[#f0ddc8] bg-[#fdf8f3]/60 p-5">
                        <div className="mb-4">
                            <h2 className="text-sm font-semibold text-ink">Stock levels</h2>
                            <p className="mt-0.5 text-xs text-subtle">
                                Leave blank if you don't want alerts for this item.
                            </p>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="minimum_stock" className="text-sm font-medium text-ink">
                                    Minimum stock
                                </Label>
                                <Input
                                    id="minimum_stock"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.minimum_stock}
                                    onChange={(e) => setData('minimum_stock', e.target.value)}
                                    placeholder="e.g. 20"
                                    className="h-10 border-[#e0d0c0] bg-white"
                                />
                                <p className="text-xs text-subtle">
                                    Shown as <span className="font-semibold text-yellow-700">Low Stock</span> at or below this.
                                </p>
                                {errors.minimum_stock && (
                                    <p className="text-sm text-danger">{errors.minimum_stock}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="critical_stock" className="text-sm font-medium text-ink">
                                    Critical stock
                                </Label>
                                <Input
                                    id="critical_stock"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.critical_stock}
                                    onChange={(e) => setData('critical_stock', e.target.value)}
                                    placeholder="e.g. 5"
                                    className="h-10 border-[#e0d0c0] bg-white"
                                />
                                <p className="text-xs text-subtle">
                                    Shown as <span className="font-semibold text-red-600">Critical</span> at or below this.
                                </p>
                                {errors.critical_stock && (
                                    <p className="text-sm text-danger">{errors.critical_stock}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Description */}
                    <div className="space-y-1.5">
                        <Label htmlFor="description" className="text-sm font-medium text-ink">
                            Description
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Optional notes about this item"
                            rows={3}
                            className="resize-none border-[#e0d0c0]"
                        />
                        {errors.description && (
                            <p className="text-sm text-danger">{errors.description}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4 border-t border-[#f0ddc8] pt-5">
                        <Link
                            href={inventoryItemsIndex().url}
                            className="text-sm font-medium text-subtle hover:text-ink"
                        >
                            Cancel
                        </Link>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-10 bg-brand-orange px-6 font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Create item
                        </Button>
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