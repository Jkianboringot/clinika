import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/products';

interface Product {
    id: number;
    name: string;
    price: number;
    cost: number;
}

interface Props {
    products: Product;
}

export default function Edit({ products }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: products.name,
        price: products.price,
        cost: products.cost,
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(products.id).url);
    };

    return (
        <>
            <Head title="Edit Product" />

            <div className="mx-auto w-full  max-w-4xl p-6">

                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Product</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update details for "{products.name}".
                    </p>
                </div>

                <form
                    onSubmit={handleUpdate}
                    className="space-y-4 rounded-xl border border-[#f0ddc8] bg-white p-5"
                >
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="space-y-1">
                        <Label htmlFor="name" className="font-semibold text-ink">
                            Name
                        </Label>
                        <Input
                            id="name"
                            placeholder="Product name"
                            value={data.name}
                            minLength={3}
                            maxLength={75}
                            type='text'
                            onChange={(e) => setData('name', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}

                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="price" className="font-semibold text-ink">
                                Price
                            </Label>
                            <Input
                                id="price"
                                type="number"
                                step="0.01"
                                min="1"
                                max="99999"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                        {errors.price && <p className="mt-1.5 text-sm text-danger">{errors.price}</p>}

                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="cost" className="font-semibold text-ink">
                                Cost
                            </Label>
                            <Input
                                id="cost"
                                type="number"
                                step="0.01"
                                min="1"
                                max="99999"
                                value={data.cost}
                                onChange={(e) => setData('cost', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                        {errors.cost && <p className="mt-1.5 text-sm text-danger">{errors.cost}</p>}

                        </div>
                    </div>

                    <div className="flex justify-end border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Update Product
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
            title: 'Edit Product',
        },
    ],
};