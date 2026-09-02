import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { store } from '@/routes/branches';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';


interface Branch {
    id: number;
    name: string;
    location: string | '';

    // HACK - make this enum interface
    branch_type: string;
}

interface SelectOption {
    value: string;
    label: string;
}


interface Props {
    branch_types: SelectOption[];
    branches: Branch;
}




export default function Edit({ branches, branch_types }: Props) {
    // HACK - useForm should have type
    const { data, setData, post, processing, errors } = useForm({
        name: branches.name,
        location: branches?.location,
        branch_type: branches.branch_type,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Edit Branch" />

            <div className="mx-auto w-full  max-w-4xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Product</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update details for "{branches.name}".
                    </p>
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
                                <ul className="list-inside list-disc text-sm">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}


                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="name" className="font-semibold text-ink">
                                Branch Name
                            </Label>
                            <Input
                                id="name"
                                type='text'
                                placeholder="Branch name"
                                value={data.name}
                                minLength={3}
                                maxLength={75}
                                onChange={(e) => setData('name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}

                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="location" className="font-semibold text-ink">
                                Branch Location
                            </Label>
                            <Input
                                id="location"
                                type='text'
                                placeholder="Branch location"
                                value={data.location}
                                minLength={3}
                                maxLength={100}
                                onChange={(e) => setData('location', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.location && <p className="mt-1.5 text-sm text-danger">{errors.location}</p>}

                        </div>


                    </div>

                    <div>
                        <Label htmlFor="branch_type">Branch Type</Label>
                        <Select value={data.branch_type || undefined} onValueChange={(value) => setData('branch_type', value)}>
                            <SelectTrigger id="branch_type" className="mt-1.5 w-full bg-white sm:max-w-xs">
                                <SelectValue placeholder="Select branch type…" />
                            </SelectTrigger>
                            <SelectContent>
                                {branch_types.map((v) => (
                                    <SelectItem key={v.value} value={v.value}>
                                        {v.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.branch_type && <p className="mt-1.5 text-sm text-danger">{errors.branch_type}</p>}
                    </div>


                    <div className="flex justify-end border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Save Branch
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
            title: 'Edit  Branch',
        },
    ],
};