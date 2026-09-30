import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CircleAlert, Plus, Trash2 } from 'lucide-react';
import { index, update } from '@/routes/inventories';

interface Item {
    id: number;
    name: string;
}

interface ItemRow {
    item_id: string;
    quantity: string;
}

interface InventoryForm {
    itemList: ItemRow[];
}

interface InventoryPayload {
    id: number;
    staff_id: number;
    created_at: string;
    itemList: { item_id: number | string; quantity: number | string }[];
}

interface Props {
    inventory: InventoryPayload;
    items: Item[];
}

const emptyItem: ItemRow = { item_id: '', quantity: '1' };

export default function Edit({ inventory, items }: Props) {
    const { data, setData, put, processing, errors } = useForm<InventoryForm>({
        // server sends numbers; the Select and Input work with strings
        itemList:
            inventory.itemList.length > 0
                ? inventory.itemList.map((row) => ({
                      item_id: String(row.item_id),
                      quantity: String(row.quantity),
                  }))
                : [{ ...emptyItem }],
    });

    // nested errors come back keyed like "itemList.0.item_id"
    const fieldError = (key: string) => (errors as Record<string, string>)[key];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(inventory.id).url);
    };

    // ---- item rows ----
    const addItem = () => setData('itemList', [...data.itemList, { ...emptyItem }]);

    const updateItem = (i: number, field: keyof ItemRow, value: string) =>
        setData(
            'itemList',
            data.itemList.map((row, idx) => (idx === i ? { ...row, [field]: value } : row)),
        );

    const removeItem = (i: number) =>
        setData(
            'itemList',
            data.itemList.filter((_, idx) => idx !== i),
        );

    return (
        <>
            <Head title={`Edit Stock #${inventory.id}`} />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Stock</h1>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-[#f0ddc8] bg-white p-5"
                >
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm text-danger">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Items (increases stock) */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label className="font-semibold text-ink">Items</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addItem}
                                className="border-[#e0d0c0]"
                            >
                                <Plus className="h-4 w-4" />
                                Add item
                            </Button>
                        </div>

                        {data.itemList.length === 0 && (
                            <p className="text-sm text-subtle">
                                No items yet. An inventory entry needs at least one item.
                            </p>
                        )}

                        {data.itemList.map((row, i) => (
                            <div key={i} className="grid grid-cols-[1fr_8rem_auto] items-start gap-3">
                                <div className="space-y-1">
                                    <Select
                                        value={row.item_id}
                                        onValueChange={(value) => updateItem(i, 'item_id', value)}
                                    >
                                        <SelectTrigger className="border-[#e0d0c0]">
                                            <SelectValue placeholder="Select item" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {items.map((item) => (
                                                <SelectItem key={item.id} value={String(item.id)}>
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {fieldError(`itemList.${i}.item_id`) && (
                                        <p className="mt-1.5 text-sm text-danger">
                                            {fieldError(`itemList.${i}.item_id`)}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <Input
                                        type="number"
                                        min={1}
                                        max={9999}
                                        placeholder="Qty"
                                        value={row.quantity}
                                        onChange={(e) => updateItem(i, 'quantity', e.target.value)}
                                        className="border-[#e0d0c0]"
                                    />
                                    {fieldError(`itemList.${i}.quantity`) && (
                                        <p className="mt-1.5 text-sm text-danger">
                                            {fieldError(`itemList.${i}.quantity`)}
                                        </p>
                                    )}
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label="Remove item"
                                    onClick={() => removeItem(i)}
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        {errors.itemList && (
                            <p className="mt-1.5 text-sm text-danger">{errors.itemList}</p>
                        )}
                    </div>

                    <div className="flex justify-end gap-2 border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Update Inventory
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Inventory',
            href: index().url,
        },
        {
            title: 'Edit Stock',
            href: '#',
        },
    ],
};