import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/users';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    users: User;
}

export default function Edit({ users }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: users.name,
        email: users.email,
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(users.id).url);
    };

    return (
        <>
            <Head title="Edit User" />

            <div className="mx-auto w-full  max-w-4xl p-6">

                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit User</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update details for "{users.name}".
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
                            placeholder="User name"
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
                            <Label htmlFor="email" className="font-semibold text-ink">
                                Email
                            </Label>
                            <Input
                                id="price"
                                type="number"
                                step="0.01"
                                min="1"
                                max="99999"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                        {errors.email && <p className="mt-1.5 text-sm text-danger">{errors.email}</p>}

                        </div>

                    </div>

                    <div className="flex justify-end border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Update User
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
            title: 'Edit User',
        },
    ],
};