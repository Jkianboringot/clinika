import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Trash2 } from 'lucide-react';
import { createIn, storeIn } from '@/routes/inventories';

interface Branch {
    id: number;
    location: string;
    branch_type: string;
}

interface Product {
    id: number;
    name: string;
    price: number;
}

interface ProductRow {
    product_id: number | '';
    quantity: number;
}

interface Props {
    branches: Branch[];
    products: Product[];
    stockMovementTypes: { value: string; label: string }[];
}

export default function CreateIn({ branches, products, stockMovementTypes }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        branch_id: number | '';
        stock_movement_type: string;
        productList: ProductRow[];
    }>({
        branch_id: '',
        stock_movement_type: 'delivery',
        productList: [{ product_id: '', quantity: 1 }],
    });

    function addRow() {
        setData('productList', [...data.productList, { product_id: '', quantity: 1 }]);
    }

    function removeRow(index: number) {
        setData(
            'productList',
            data.productList.filter((_, i) => i !== index),
        );
    }

    function updateRow(index: number, key: keyof ProductRow, value: string | number) {
        const rows = [...data.productList];
        rows[index] = { ...rows[index], [key]: value };
        setData('productList', rows);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        
        post(storeIn().url);
    }

    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-3xl font-extrabold tracking-tight text-ink">Inventory In</h1>
            </div>

            <form onSubmit={submit} className="w-full">
                <div className="w-full overflow-hidden rounded-xl border border-[#f0ddc8] bg-[#fdf8f2]">
                    {/* Details */}
                    <div className="border-b border-[#f0ddc8] p-6">
                        <h2 className="mb-4 text-sm font-semibold text-ink">Details</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="branch_id">Branch</Label>
                                <Select
                                    value={data.branch_id ? String(data.branch_id) : undefined}
                                    onValueChange={(value) => setData('branch_id', Number(value))}
                                >
                                    <SelectTrigger id="branch_id" className="mt-1.5 w-full bg-white">
                                        <SelectValue placeholder="Select a branch…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {branches.map((b) => (
                                            <SelectItem key={b.id} value={String(b.id)}>
                                                {b.location} ({b.branch_type})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.branch_id && <p className="mt-1.5 text-sm text-danger">{errors.branch_id}</p>}
                            </div>

                            <div>
                                <Label htmlFor="stock_movement_type">Stock Type</Label>
                                <Select
                                    value={data.stock_movement_type || undefined}
                                    onValueChange={(value) => setData('stock_movement_type', value)}
                                >
                                    <SelectTrigger id="stock_movement_type" className="mt-1.5 w-full bg-white">
                                        <SelectValue placeholder="Select stock movement…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {stockMovementTypes.map((t) => (
                                            <SelectItem key={t.value} value={t.value}>
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    {/* Products */}
                    <div className="p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-ink">Products Received</h2>
                            <Button type="button" variant="ghost" size="sm" onClick={addRow} className="text-brand-orange hover:text-brand-orange-hover">
                                <Plus className="h-4 w-4" />
                                Add Product
                            </Button>
                        </div>

                        <div className="space-y-3">
                            {data.productList.map((row, i) => (
                                <div
                                    key={i}
                                    className="flex flex-col gap-3 rounded-lg border border-[#f0ddc8] bg-white/60 p-3 sm:flex-row sm:items-start"
                                >
                                    <Select
                                        value={row.product_id ? String(row.product_id) : undefined}
                                        onValueChange={(value) => updateRow(i, 'product_id', Number(value))}
                                    >
                                        <SelectTrigger className="flex-1 bg-white">
                                            <SelectValue placeholder="Select product…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {products.map((p) => (
                                                <SelectItem key={p.id} value={String(p.id)}>
                                                    {p.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        className="bg-white sm:w-28"
                                        placeholder="Qty"
                                        value={row.quantity}
                                        onChange={(e) => updateRow(i, 'quantity', Number(e.target.value))}
                                    />

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeRow(i)}
                                        disabled={data.productList.length === 1}
                                        className="shrink-0 text-danger hover:bg-danger/10 hover:text-danger disabled:opacity-40"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        Remove
                                    </Button>
                                </div>
                            ))}
                        </div>
                        {errors.productList && <p className="mt-2 text-sm text-danger">{errors.productList}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end border-t border-[#f0ddc8] bg-white/60 px-6 py-4">
                        <Button type="submit" disabled={processing} className="bg-green-600 font-bold text-white hover:bg-green-700">
                            {processing ? 'Saving…' : 'Save Stock In'}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}