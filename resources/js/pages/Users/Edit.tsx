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
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/users';
import DentistFields, {
    buildDentistForm,
    dentistPayload,
    DentistForm,
} from '@/components/dentist-fields';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    // saved details from the controller (null when the user is not a dentist)
    dentist?: Parameters<typeof buildDentistForm>[0];
}

interface UserForm {
    name: string;
    email: string;
    role: string;
    dentist: DentistForm;
}

interface EditProps {
    user: User;
    assignableRoles: string[];
}

const ROLE_LABELS: Record<string, string> = {
    dentist: 'Dentist',
    staff: 'Staff',
};

export default function Edit({ user, assignableRoles }: EditProps) {
    const { data, setData, put, processing, errors, transform } = useForm<UserForm>({
        name: user.name,
        email: user.email,
        role: user.role,
        dentist: buildDentistForm(user.dentist),
    });

    // Sends dentist data only when the role is dentist.
    transform((d) => ({
        ...d,
        dentist: d.role === 'dentist' ? dentistPayload(d.dentist) : undefined,
    }));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(user.id).url);
    };

    return (
        <>
            <Head title="Edit User" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit User</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update this user's account information.
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
                                <ul className="list-inside list-disc text-sm text-danger">
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
                            type="text"
                            placeholder="User name"
                            value={data.name}
                            minLength={3}
                            maxLength={75}
                            onChange={(e) => setData('name', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && (
                            <p className="mt-1.5 text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="email" className="font-semibold text-ink">
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="Email address"
                                value={data.email}
                                minLength={3}
                                maxLength={150}
                                onChange={(e) => setData('email', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-sm text-danger">{errors.email}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="role" className="font-semibold text-ink">
                                Role
                            </Label>
                            <Select
                                value={data.role}
                                onValueChange={(value) => setData('role', value)}
                            >
                                <SelectTrigger id="role" className="border-[#e0d0c0]">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {assignableRoles.map((role) => (
                                        <SelectItem key={role} value={role}>
                                            {ROLE_LABELS[role] ?? role}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.role && (
                                <p className="mt-1.5 text-sm text-danger">{errors.role}</p>
                            )}
                        </div>
                    </div>

                    {data.role === 'dentist' && (
                        <DentistFields
                            value={data.dentist}
                            onChange={(dentist) => setData('dentist', dentist)}
                            errors={errors as Record<string, string>}
                        />
                    )}

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
            href: '/users',
        },
    ],
};